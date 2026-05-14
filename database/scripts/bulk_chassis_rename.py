#!/usr/bin/env python3
"""One-off bulk replace: sub_products.product_no -> chassis_no (run from repo root)."""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SKIP_PARTS = {"vendor", "node_modules", "storage", "bootstrap", ".git"}

# (old, new) — order matters; longer / more specific first where needed.
REPLACEMENTS: list[tuple[str, str]] = [
    ("sub_products.product_no", "sub_products.chassis_no"),
    ("SubProduct::where('product_no',", "SubProduct::where('chassis_no',"),
    ('SubProduct::where("product_no",', 'SubProduct::where("chassis_no",'),
    ("SubProduct::whereIn('product_no',", "SubProduct::whereIn('chassis_no',"),
    ("\\App\\Models\\SubProduct::where('product_no',", "\\App\\Models\\SubProduct::where('chassis_no',"),
    ("SubProduct::whereRaw('UPPER(TRIM(product_no))", "SubProduct::whereRaw('UPPER(TRIM(chassis_no))"),
    ("SubProduct::whereRaw('TRIM(product_no) = ?", "SubProduct::whereRaw('TRIM(chassis_no) = ?"),
    ("$subProduct->product_no", "$subProduct->chassis_no"),
    ("subProduct->product_no", "subProduct->chassis_no"),
    ("'sp.product_no',", "'sp.chassis_no as product_no',"),
    ("whereIn('sp.product_no',", "whereIn('sp.chassis_no',"),
    ("groupBy('sp.product_no',", "groupBy('sp.chassis_no',"),
    ("->orderBy('product_no')", "->orderBy('chassis_no')"),
    ("->orderBy(\"product_no\")", "->orderBy(\"chassis_no\")"),
    ("SubProduct::select('id', 'product_no'", "SubProduct::select('id', 'chassis_no'"),
    ("SubProduct::select('id', 'product_no',", "SubProduct::select('id', 'chassis_no',"),
    ("->select('id', 'product_no', 'product_id'", "->select('id', 'chassis_no', 'product_id'"),
    ("->select('id', 'product_no', 'product_id', 'sale_price'", "->select('id', 'chassis_no', 'product_id', 'sale_price'"),
    ("->select('id', 'product_no', 'warehouse_id'", "->select('id', 'chassis_no', 'warehouse_id'"),
    ("->select('sp.id','sp.product_no'", "->select('sp.id','sp.chassis_no as product_no'"),
    ("whereIn(DB::raw('UPPER(TRIM(product_no))')", "whereIn(DB::raw('UPPER(TRIM(chassis_no))')"),
    ("->select('id', 'product_no')", "->select('id', 'chassis_no')"),
    ("'id', 'product_no', 'product_id', 'quantity', 'booked'", "'id', 'chassis_no', 'product_id', 'quantity', 'booked'"),
    ("'id', 'product_no', 'product_id', 'quantity', 'warehouse_id'", "'id', 'chassis_no', 'product_id', 'quantity', 'warehouse_id'"),
]


def iter_files() -> list[Path]:
    out: list[Path] = []
    for base in (ROOT / "app", ROOT / "resources"):
        if not base.is_dir():
            continue
        for p in base.rglob("*"):
            if p.suffix not in {".php", ".blade.php"}:
                continue
            if any(part in SKIP_PARTS for part in p.parts):
                continue
            out.append(p)
    return out


def main() -> int:
    changed = 0
    for path in iter_files():
        try:
            text = path.read_text(encoding="utf-8")
        except OSError:
            continue
        orig = text
        for old, new in REPLACEMENTS:
            if old == new:
                continue
            text = text.replace(old, new)
        if text != orig:
            path.write_text(text, encoding="utf-8", newline="\n")
            changed += 1
            print(path.relative_to(ROOT))
    print(f"Updated {changed} files.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
