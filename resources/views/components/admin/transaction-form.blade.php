@props([
    'action',
    'method' => 'POST',
    'students' => collect(),
    'selectedStudentId' => null,
    'tanggal' => null,
    'jumlah' => null,
    'periodOptions' => collect(),
    'selectedPeriods' => [],
    'submitLabel' => 'Simpan transaksi',
    'studentLocked' => false,
    'studentLabel' => null,
])

@php
    $groupedPeriods = collect($periodOptions)
        ->groupBy('tahun')
        ->sortKeys();

    $selectedPeriodValues = collect($selectedPeriods)
        ->filter(fn ($value) => filled($value))
        ->map(fn ($value) => (string) $value)
        ->values();

    $selectedPeriodMap = collect($periodOptions)
        ->keyBy('value')
        ->mapWithKeys(fn (array $period, string $value) => [$value => $period['label']]);

    $selectedPeriodLabels = $selectedPeriodValues
        ->map(fn (string $value): array => [
            'value' => $value,
            'label' => $selectedPeriodMap[$value] ?? $value,
        ])
        ->all();

    $amountValue = filled($jumlah) ? (int) $jumlah : null;
    $amountDisplay = $amountValue !== null ? 'Rp' . number_format($amountValue, 0, ',', '.') : '';
    $initialYear = $selectedPeriodValues
        ->map(fn (string $value) => (int) substr($value, 0, 4))
        ->first() ?? $groupedPeriods->keys()->first();
@endphp

<section class="dme-section-card">
    <form
        method="POST"
        action="{{ $action }}"
        class="dme-form-stack"
        data-transaction-form
        data-selected-periods='@json($selectedPeriodValues->all())'
        data-initial-year="{{ $initialYear }}"
    >
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="dme-field">
            <label for="id_murid" class="dme-field__label">Murid</label>

            @if ($studentLocked)
                <input type="hidden" name="id_murid" value="{{ $selectedStudentId }}">
                <div class="transaction-pill transaction-pill--static">{{ $studentLabel }}</div>
            @else
                <select class="dme-field__control" id="id_murid" name="id_murid" required>
                    <option value="">Pilih murid</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->getKey() }}" @selected((string) $selectedStudentId === (string) $student->getKey())>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="transaction-form-grid">
            <div class="dme-field">
                <label for="tanggal" class="dme-field__label">Tanggal</label>
                <input class="dme-field__control" id="tanggal" name="tanggal" type="date" value="{{ $tanggal }}" required>
            </div>

            <div class="dme-field">
                <label for="jumlah" class="dme-field__label">Jumlah</label>
                <input type="hidden" id="jumlah" name="jumlah" value="{{ $amountValue }}" data-transaction-amount-hidden>
                <input
                    class="dme-field__control"
                    id="jumlah_display"
                    type="text"
                    inputmode="numeric"
                    placeholder="Rp0"
                    value="{{ $amountDisplay }}"
                    required
                    data-transaction-amount-display
                >
            </div>
        </div>

        <div class="dme-field">
            <span class="dme-field__label">Bulan yang dibayar</span>
            <div class="transaction-period-summary">
                <button
                    type="button"
                    class="dme-button--secondary transaction-period-summary__trigger"
                    data-transaction-period-open
                >
                    Pilih bulan
                </button>

                <div class="transaction-inline-pills transaction-period-summary__list" data-transaction-selected-pills>
                    @forelse ($selectedPeriodLabels as $period)
                        <span class="transaction-inline-pill" data-transaction-pill="{{ $period['value'] }}">{{ $period['label'] }}</span>
                    @empty
                        <p class="transaction-period-summary__placeholder" data-transaction-empty-state>Belum ada bulan dipilih</p>
                    @endforelse
                </div>

                <div data-transaction-period-inputs>
                    @foreach ($selectedPeriodValues as $value)
                        <input type="hidden" name="periods[]" value="{{ $value }}">
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dme-action-row">
            <button type="submit" class="dme-button">{{ $submitLabel }}</button>
        </div>

        <div class="teacher-dialog transaction-period-dialog" data-transaction-period-dialog hidden>
            <div class="teacher-dialog__backdrop" data-transaction-period-cancel></div>
            <div class="teacher-dialog__panel transaction-period-dialog__panel">
                <div class="transaction-period-dialog__header">
                    <div>
                        <h3 class="teacher-dialog__title transaction-period-dialog__title">Pilih bulan pembayaran</h3>
                        <p class="teacher-dialog__copy transaction-period-dialog__copy">Pilih satu atau beberapa bulan yang dibayar dalam transaksi ini.</p>
                    </div>
                    <button type="button" class="transaction-period-dialog__close" aria-label="Tutup" data-transaction-period-cancel>&times;</button>
                </div>

                <div class="transaction-period-dialog__year-tabs">
                    @foreach ($groupedPeriods as $year => $periods)
                        <button
                            type="button"
                            class="transaction-year-tab"
                            data-transaction-year-tab="{{ $year }}"
                        >
                            {{ $year }}
                        </button>
                    @endforeach
                </div>

                <div class="transaction-period-dialog__months">
                    @foreach ($groupedPeriods as $year => $periods)
                        <div class="transaction-period-dialog__month-grid" data-transaction-year-panel="{{ $year }}">
                            @foreach ($periods as $period)
                                <button
                                    type="button"
                                    class="transaction-month-pill"
                                    data-transaction-period-toggle="{{ $period['value'] }}"
                                    data-transaction-period-label="{{ $period['label'] }}"
                                >
                                    {{ $period['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="teacher-dialog__actions transaction-period-dialog__actions">
                    <button type="button" class="teacher-dialog__button teacher-dialog__button--secondary" data-transaction-period-cancel>Batal</button>
                    <button type="button" class="teacher-dialog__button teacher-dialog__button--primary" data-transaction-period-confirm>Simpan pilihan</button>
                </div>
            </div>
        </div>
    </form>
</section>

@push('scripts')
    <script>
        document.querySelectorAll('[data-transaction-form]').forEach((form) => {
            if (form.dataset.transactionFormReady === 'true') {
                return;
            }

            form.dataset.transactionFormReady = 'true';

            const amountDisplay = form.querySelector('[data-transaction-amount-display]');
            const amountHidden = form.querySelector('[data-transaction-amount-hidden]');
            const dialog = form.querySelector('[data-transaction-period-dialog]');
            const openButton = form.querySelector('[data-transaction-period-open]');
            const confirmButton = form.querySelector('[data-transaction-period-confirm]');
            const cancelButtons = form.querySelectorAll('[data-transaction-period-cancel]');
            const yearTabs = form.querySelectorAll('[data-transaction-year-tab]');
            const yearPanels = form.querySelectorAll('[data-transaction-year-panel]');
            const monthButtons = form.querySelectorAll('[data-transaction-period-toggle]');
            const pillsContainer = form.querySelector('[data-transaction-selected-pills]');
            const hiddenInputsContainer = form.querySelector('[data-transaction-period-inputs]');
            const initialSelected = JSON.parse(form.dataset.selectedPeriods || '[]');
            const initialYear = form.dataset.initialYear;

            let selectedPeriods = new Set(initialSelected);
            let workingPeriods = new Set(selectedPeriods);
            let activeYear = initialYear;

            const sortedValues = (values) => [...values].sort();

            const formatCurrency = (value) => {
                if (! value) {
                    return '';
                }

                return 'Rp' + new Intl.NumberFormat('id-ID').format(value);
            };

            const parseCurrency = (value) => Number(String(value).replace(/[^\d]/g, '')) || 0;

            const syncAmount = () => {
                const value = parseCurrency(amountDisplay.value);

                amountHidden.value = value > 0 ? value : '';
                amountDisplay.value = value > 0 ? formatCurrency(value) : '';
            };

            const renderSelectedPeriods = () => {
                const values = sortedValues(selectedPeriods);

                hiddenInputsContainer.innerHTML = values
                    .map((value) => `<input type="hidden" name="periods[]" value="${value}">`)
                    .join('');

                pillsContainer.innerHTML = '';

                if (values.length === 0) {
                    const emptyState = document.createElement('p');
                    emptyState.className = 'transaction-period-summary__placeholder';
                    emptyState.dataset.transactionEmptyState = 'true';
                    emptyState.textContent = 'Belum ada bulan dipilih';
                    pillsContainer.appendChild(emptyState);
                    return;
                }

                values.forEach((value) => {
                    const button = form.querySelector(`[data-transaction-period-toggle="${value}"]`);
                    const label = button?.dataset.transactionPeriodLabel || value;
                    const pill = document.createElement('span');
                    pill.className = 'transaction-inline-pill';
                    pill.dataset.transactionPill = value;
                    pill.textContent = label;
                    pillsContainer.appendChild(pill);
                });
            };

            const setActiveYear = (year) => {
                activeYear = year;

                yearTabs.forEach((tab) => {
                    tab.classList.toggle('is-active', tab.dataset.transactionYearTab === year);
                });

                yearPanels.forEach((panel) => {
                    panel.hidden = panel.dataset.transactionYearPanel !== year;
                });
            };

            const renderWorkingSelection = () => {
                monthButtons.forEach((button) => {
                    const checked = workingPeriods.has(button.dataset.transactionPeriodToggle);
                    button.classList.toggle('is-selected', checked);
                    button.setAttribute('aria-pressed', checked ? 'true' : 'false');
                });
            };

            openButton?.addEventListener('click', () => {
                workingPeriods = new Set(selectedPeriods);
                renderWorkingSelection();
                setActiveYear(activeYear);
                dialog.hidden = false;
            });

            confirmButton?.addEventListener('click', () => {
                selectedPeriods = new Set(workingPeriods);
                renderSelectedPeriods();
                dialog.hidden = true;
            });

            cancelButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    workingPeriods = new Set(selectedPeriods);
                    renderWorkingSelection();
                    dialog.hidden = true;
                });
            });

            yearTabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    setActiveYear(tab.dataset.transactionYearTab);
                });
            });

            monthButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const value = button.dataset.transactionPeriodToggle;

                    if (workingPeriods.has(value)) {
                        workingPeriods.delete(value);
                    } else {
                        workingPeriods.add(value);
                    }

                    renderWorkingSelection();
                });
            });

            amountDisplay?.addEventListener('input', () => {
                const value = parseCurrency(amountDisplay.value);
                amountHidden.value = value > 0 ? value : '';
                amountDisplay.value = value > 0 ? formatCurrency(value) : '';
            });

            form.addEventListener('submit', () => {
                syncAmount();
                renderSelectedPeriods();
            });

            renderSelectedPeriods();
            renderWorkingSelection();
            setActiveYear(initialYear);
            syncAmount();
        });
    </script>
@endpush
