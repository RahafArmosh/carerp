{{-- Action buttons for leads table row --}}
<span>
    @if (Gate::check('view lead') || Gate::check('manage crm admin'))
        @if ($lead->is_active)
            <div class="action-btn bg-warning ms-2">
                <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-sm d-inline-flex align-items-center mx-3"
                    data-size="xl" data-bs-toggle="tooltip" title="{{ __('View') }}" data-title="{{ __('Lead Detail') }}">
                    <i class="ti ti-eye text-white"></i>
                </a>
            </div>
        @endif
    @endif
    @if (Gate::check('edit lead') || Gate::check('manage crm admin'))
        <div class="action-btn bg-info ms-2">
            <a href="#" class="btn btn-sm d-inline-flex align-items-center mx-3"
                data-url="{{ route('leads.edit', $lead->id) }}" data-ajax-popup="true" data-size="xl"
                data-bs-toggle="tooltip" title="{{ __('Edit') }}" data-title="{{ __('Lead Edit') }}">
                <i class="ti ti-pencil text-white"></i>
            </a>
        </div>
    @endif
    @if (Gate::check('delete lead') || Gate::check('manage crm admin'))
        <div class="action-btn bg-danger ms-2">
            <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" id="delete-form-{{ $lead->id }}">
                @csrf
                @method('DELETE')
                <a href="#" class="btn btn-sm align-items-center bs-pass-para mx-3" data-bs-toggle="tooltip"
                    title="{{ __('Delete') }}">
                    <i class="ti ti-trash text-white"></i>
                </a>
            </form>
        </div>
    @endif
    @if (Gate::check('convert lead to deal') || Gate::check('manage crm admin') || Gate::check('view deal'))
        @if ($lead->deals->isNotEmpty())
            @php $firstDeal = $lead->deals->first(); @endphp
            @can('View Deal')
                <a href="{{ $firstDeal->is_active ? route('deals.show', $firstDeal->id) : '#' }}" data-size="lg"
                    data-bs-toggle="tooltip" title="{{ __('View Deal') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-exchange"></i>
                </a>
            @else
                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                    title="{{ __('Already convert') }}">
                    <i class="ti ti-exchange"></i>
                </a>
            @endcan
        @else
            <a href="#" data-size="lg" data-url="{{ URL::to('leads/' . $lead->id . '/show_convert') }}"
                data-ajax-popup="true" data-bs-toggle="tooltip"
                title="{{ __('Convert [' . $lead->subject . '] To Deal') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-exchange"></i>
            </a>
        @endif
    @endif
</span>
