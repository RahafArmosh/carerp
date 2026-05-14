"use strict";
var flg = "0";

// === Sidebar open/close — registered immediately so it can never be blocked
// by an unrelated error inside DOMContentLoaded. ===
(function () {
    function getSidebarEl() {
        return document.querySelector("#sidebar");
    }

    function isSidebarOpen() {
        var s = getSidebarEl();
        return !!(s && !s.classList.contains("active"));
    }

    function setSidebarOpen(open) {
        var s = getSidebarEl();
        if (!s) return;
        // In this layout, .active = hidden.
        s.classList.toggle("active", !open);
        document.body.classList.toggle("sidebar-open", open);
        var ham = document.querySelector(".hamburger");
        if (ham) ham.classList.toggle("is-active", open);
    }

    function toggleSidebar() { setSidebarOpen(!isSidebarOpen()); }
    function closeSidebar() { setSidebarOpen(false); }

    // Expose for any inline code that wants to drive it.
    window.toggleSidebar = toggleSidebar;
    window.closeSidebar = closeSidebar;

    // Close (X) + overlay: capture phase so nothing can intercept before we run,
    // and stopImmediatePropagation so the event does not confuse other listeners.
    document.addEventListener(
        "click",
        function (e) {
            var target = e.target;
            if (!(target instanceof Element) || !target.closest) return;
            if (
                target.closest("#sidebar-close-btn") ||
                target.closest("#sidebar-overlay")
            ) {
                e.preventDefault();
                e.stopImmediatePropagation();
                closeSidebar();
            }
        },
        true
    );

    // Hamburger + outside-click (bubble).
    document.addEventListener("click", function (e) {
        var target = e.target;
        if (!(target instanceof Element) || !target.closest) return;

        if (target.closest("#mobile-collapse, .mob-hamburger")) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
            return;
        }

        if (isSidebarOpen()) {
            var sidebar = getSidebarEl();
            if (sidebar && !sidebar.contains(target)) {
                closeSidebar();
            }
        }
    });

    // Press Escape to close.
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && isSidebarOpen()) {
            closeSidebar();
        }
    });
})();

document.addEventListener("DOMContentLoaded", function () {
    try {
        if (typeof feather !== "undefined" && feather && feather.replace) {
            feather.replace();
        }
    } catch (err) {
        console.warn("dash.js: feather.replace() failed", err);
    }

    setTimeout(function () {
        var loader = document.querySelector(".loader-bg");
        if (loader) loader.remove();
    }, 400);

    try { initializeActiveMenus(); } catch (err) { console.warn("dash.js: initializeActiveMenus failed", err); }
    try { setupDropdownToggles(); } catch (err) { console.warn("dash.js: setupDropdownToggles failed", err); }

    /**
     * Find and open all submenus that contain active items
     */
    function initializeActiveMenus() {
        // Find all active menu items
        const activeItems = document.querySelectorAll(".dash-navbar .active");

        activeItems.forEach((item) => {
            // For each active item, find all parent submenus and open them
            let parent = item.closest(".dash-submenu");
            while (parent) {
                const parentMenuItem = parent.closest(".dash-item");
                if (parentMenuItem) {
                    // Add open class
                    parentMenuItem.classList.add("open");

                    // Set aria-expanded
                    const toggle = parentMenuItem.querySelector(
                        '[data-toggle="collapse"]'
                    );
                    if (toggle) {
                        toggle.setAttribute("aria-expanded", "true");
                    }
                }

                // Move up to the next parent
                parent = parentMenuItem
                    ? parentMenuItem.closest(".dash-submenu")
                    : null;
            }
        });
    }

    /**
     * Add click handlers to dropdown toggles - FIXED for deep nesting and link navigation
     */
    function setupDropdownToggles() {
        const navbar =
            document.querySelector("#sidebar .dash-navbar") ||
            document.querySelector(".dash-navbar");
        if (!navbar) {
            console.warn("dash.js: no .dash-navbar found — submenu toggles skipped.");
            return;
        }
        navbar.addEventListener("click", (e) => {
                //Check if the clicked element is a link but NOT a dropdown toggle
                if (
                    e.target.tagName === "A" &&
                    !e.target.hasAttribute("data-toggle")
                ) {
                    // This is a regular link, not a toggle - let it navigate normally
                    return;
                }

                // Check if the clicked element is a dropdown toggle or contains one
                const toggle = e.target.closest('[data-toggle="collapse"]');

                if (toggle) {
                    e.preventDefault();

                    // Find the parent menu item
                    const menuItem = toggle.closest(".dash-item");
                    if (!menuItem) return;

                    const isOpen = menuItem.classList.contains("open");

                    // If we're opening this menu, close all siblings at the SAME LEVEL
                    if (!isOpen) {
                        // Find the direct parent submenu or navbar
                        const parentMenu = menuItem.parentElement;
                        if (parentMenu) {
                            // Find all sibling menu items with submenus AT THE SAME LEVEL
                            const siblingMenuItems =
                                parentMenu.querySelectorAll(
                                    ":scope > .dash-item.open"
                                );

                            siblingMenuItems.forEach((sibling) => {
                                if (sibling !== menuItem) {
                                    // Close this sibling
                                    sibling.classList.remove("open");

                                    // Update aria-expanded
                                    const siblingToggle = sibling.querySelector(
                                        '[data-toggle="collapse"]'
                                    );
                                    if (siblingToggle) {
                                        siblingToggle.setAttribute(
                                            "aria-expanded",
                                            "false"
                                        );
                                    }
                                }
                            });
                        }
                    }

                    // Toggle this menu
                    menuItem.classList.toggle("open");
                    toggle.setAttribute("aria-expanded", !isOpen);

                    // Stop event propagation to prevent parent handlers from firing
                    e.stopPropagation();
                }
            });
    }

    // notification scrollbar start
    if (document.querySelector(".drp-notification .noti-body")) {
        var px = new PerfectScrollbar(".drp-notification .noti-body", {
            wheelSpeed: 0.5,
            swipeEasing: 0,
            suppressScrollX: !0,
            wheelPropagation: 1,
            minScrollbarLength: 40,
        });
    }
    // notification scrollbar end
});

var emailmorelink = document.querySelector(".email-more-link");
if (emailmorelink) {
    emailmorelink.addEventListener("click", function (e) {
        document.querySelector(this).children("span").slideToggle(1);
    });
}

window.addEventListener("load", function () {
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    var popoverTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="popover"]')
    );
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    var toastElList = [].slice.call(document.querySelectorAll(".toast"));
    var toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl);
    });
});

// scroll to active menu
function scrolltargetmenu(value) {
    document.addEventListener("DOMContentLoaded", function () {
        if (document.querySelector(".navbar-content")) {
            var elm = value;
            var off = elm.getBoundingClientRect();
            var t = off.top;
            if (t > 300) {
                document.querySelector(".navbar-content").scrollTop = t - 300;
            }
        }
    });
}

if (document.querySelector("body").classList.contains("layout-topbar")) {
    var tplink = document.querySelectorAll(
        ".dash-header .list-unstyled > .dropdown"
    );
    for (var t = 0; t < tplink.length; t++) {
        var c = tplink[t];
        c.addEventListener("mouseenter", showmenu);
        c.addEventListener("mouseleave", hidemenu);
    }
}

function showmenu(event) {
    event.target.children[1].classList.add("show");
}

function hidemenu(event) {
    event.target.children[1].classList.remove("show");
}
// topbar Layout end

var tc = document.querySelectorAll(".prod-likes .form-check-input");
for (var t = 0; t < tc.length; t++) {
    var prodlike = tc[t];
    prodlike.addEventListener("change", function (event) {
        if (event.currentTarget.checked) {
            prodlike = event.target;
            // console.log(prodlike.parentNode);
            prodlike.parentNode.insertAdjacentHTML(
                "beforeend",
                '<div class="dash-like"><div class="like-wrapper"><span><span class="dash-group"><span class="dash-dots"></span><span class="dash-dots"></span><span class="dash-dots"></span><span class="dash-dots"></span></span></span></div></div>'
            );
            prodlike.parentNode
                .querySelector(".dash-like")
                .classList.add("dash-like-animate");
            setTimeout(function () {
                prodlike.parentNode.querySelector(".dash-like").remove();
            }, 3000);
        } else {
            prodlike = event.target;
            prodlike.parentNode.querySelector(".dash-like").remove();
        }
    });
}

// =======================================================
// =======================================================
let slideUp = (target, duration = 0) => {
    target.style.transitionProperty = "height, margin, padding";
    target.style.transitionDuration = duration + "ms";
    target.style.boxSizing = "border-box";
    target.style.height = target.offsetHeight + "px";
    target.offsetHeight;
    target.style.overflow = "hidden";
    target.style.height = 0;
    target.style.paddingTop = 0;
    target.style.paddingBottom = 0;
    target.style.marginTop = 0;
    target.style.marginBottom = 0;
};
let slideDown = (target, duration = 0) => {
    target.style.removeProperty("display");
    let display = window.getComputedStyle(target).display;

    if (display === "none") display = "block";

    target.style.display = display;
    let height = target.offsetHeight;
    target.style.overflow = "hidden";
    target.style.height = 0;
    target.style.paddingTop = 0;
    target.style.paddingBottom = 0;
    target.style.marginTop = 0;
    target.style.marginBottom = 0;
    target.offsetHeight;
    target.style.boxSizing = "border-box";
    target.style.transitionProperty = "height, margin, padding";
    target.style.transitionDuration = duration + "ms";
    target.style.height = height + "px";
    target.style.removeProperty("padding-top");
    target.style.removeProperty("padding-bottom");
    target.style.removeProperty("margin-top");
    target.style.removeProperty("margin-bottom");
    window.setTimeout(() => {
        target.style.removeProperty("height");
        target.style.removeProperty("overflow");
        target.style.removeProperty("transition-duration");
        target.style.removeProperty("transition-property");
    }, duration);
};
var slideToggle = (target, duration = 0) => {
    if (window.getComputedStyle(target).display === "none") {
        return slideDown(target, duration);
    } else {
        return slideUp(target, duration);
    }
};
// =======================================================
// =======================================================
