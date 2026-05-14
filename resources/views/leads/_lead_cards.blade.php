{{-- @foreach ($leads as $lead) --}}
    <div class="card" data-id="{{ $lead->id }}">
        <div class="pt-3 ps-3">
            @php($labels = $lead->labels())
            @if ($labels)
                @foreach ($labels as $label)
                    <div class="badge-xs badge bg-{{ $label->color }} p-2 px-3 rounded">
                        {{ $label->name }}</div>
                @endforeach
            @endif
        </div>
        <div class="card-header border-0 pb-0 position-relative">
            <h5><a
                    href="@can('view lead')@if ($lead->is_active){{ route('leads.show', $lead->id) }}@else#@endif @else#@endcan">{{ !empty($lead->name) ? $lead->name : $lead->email }}</a>
            </h5>
            </br>
            <h5><a
                    href="@can('view lead')@if ($lead->is_active){{ route('leads.show', $lead->id) }}@else#@endif @else#@endcan">{{ $lead->subject }}</a>
            </h5>
            </br>
            <h5>
                @if (!empty($lead->whatsapp))
                    <?php
                        $whatsapp = preg_replace('/\D+/', '', $lead->whatsapp);
                        if (substr($whatsapp, 0, 3) == '971') {
                            $formattedNumber = $whatsapp;
                        } elseif (substr($whatsapp, 0, 1) == '0') {
                            $formattedNumber = '971' . substr($whatsapp, 1);
                        } else {
                            $formattedNumber = $whatsapp;
                        }
                    ?>
                    <a href="https://wa.me/{{ $formattedNumber }}" class="whatsapp-link" data-lead-id="{{ $lead->id }}" target="_blank" rel="noopener noreferrer">
                        {{ $lead->whatsapp }}
                    </a>
                @endif
            </h5>
            <div class="card-header-right">
                @if (Auth::user()->type != 'client')
                    <div class="btn-group card-option">
                        <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('edit lead')
                                <a href="#!" data-size="md" data-url="{{ URL::to('leads/' . $lead->id . '/labels') }}"
                                    data-ajax-popup="true" class="dropdown-item"
                                    data-bs-original-title="{{ __('Labels') }}">
                                    <i class="ti ti-bookmark"></i>
                                    <span>{{ __('Labels') }}</span>
                                </a>



                                <a href="#!" data-size="lg" data-url="{{ URL::to('leads/' . $lead->id . '/edit') }}"
                                    data-ajax-popup="true" class="dropdown-item"
                                    data-bs-original-title="{{ __('Edit Lead') }}">
                                    <i class="ti ti-pencil"></i>
                                    <span>{{ __('Edit') }}</span>
                                </a>
                            @endcan
                            @can('delete lead')
                                <form method="POST" action="{{ route('leads.destroy', $lead->id) }}"
                                    id="delete-form-{{ $lead->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#!" class="dropdown-item bs-pass-para"
                                        onclick="event.preventDefault(); document.getElementById('delete-form-{{ $lead->id }}').submit();">
                                        <i class="ti ti-archive"></i>
                                        <span> {{ __('Delete') }} </span>
                                    </a>
                                </form>
                            @endcan


                        </div>
                    </div>
                @endif
            </div>
        </div>
        <?php
        $products = $lead->products();
        $sources = $lead->sources();
        ?>
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <ul class="list-inline mb-0">

                    <li class="list-inline-item d-inline-flex align-items-center" data-bs-toggle="tooltip"
                        title="{{ __('Product') }}">
                        <i class="f-16 text-primary ti ti-shopping-cart"></i>
                        {{ count($products) }}
                    </li>

                    <li class="list-inline-item d-inline-flex align-items-center" data-bs-toggle="tooltip"
                        title="{{ __('Source') }}">
                        <i class="f-16 text-primary ti ti-social"></i>{{ count($sources) }}
                    </li>
                </ul>
                <div class="user-group">
                    @foreach ($lead->users as $user)
                        {{ $user->name }}
                    @endforeach
                </div>
            </div>
        </div>
    </div>
{{-- @endforeach --}}
