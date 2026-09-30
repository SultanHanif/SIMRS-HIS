const sidebar = document.querySelector('#sidebar');
const overlay = document.querySelector('#sidebar-overlay');
const openButton = document.querySelector('#sidebar-open');

function closeSidebar() {
    sidebar?.classList.add('-translate-x-full');
    overlay?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

openButton?.addEventListener('click', () => {
    sidebar?.classList.remove('-translate-x-full');
    overlay?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
});

document.querySelectorAll('[data-sidebar-close]').forEach((element) => {
    element.addEventListener('click', closeSidebar);
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const inputId = button.getAttribute('aria-controls');
        const input = inputId ? document.getElementById(inputId) : null;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const shouldShowPassword = input.type === 'password';
        input.type = shouldShowPassword ? 'text' : 'password';
        button.textContent = shouldShowPassword ? 'Sembunyikan' : 'Tampilkan';
        button.setAttribute('aria-label', shouldShowPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        button.setAttribute('aria-pressed', String(shouldShowPassword));
    });
});

const duplicateCheck = document.querySelector('[data-patient-duplicate-check]');

if (duplicateCheck instanceof HTMLElement) {
    const nameInput = document.querySelector('#name');
    const nationalIdInput = document.querySelector('#national_id');
    const dateOfBirthInput = document.querySelector('#date_of_birth');
    const message = duplicateCheck.querySelector('[data-duplicate-message]');
    const matchesList = duplicateCheck.querySelector('[data-duplicate-matches]');
    const endpoint = duplicateCheck.dataset.endpoint;
    const registerUrl = duplicateCheck.dataset.registerUrl;
    let debounceTimer;
    let activeRequest;

    function clearMatches() {
        if (matchesList instanceof HTMLUListElement) {
            matchesList.replaceChildren();
        }
    }

    function checkForPossibleDuplicates() {
        window.clearTimeout(debounceTimer);
        activeRequest?.abort();

        if (
            !(nameInput instanceof HTMLInputElement)
            || !(nationalIdInput instanceof HTMLInputElement)
            || !(dateOfBirthInput instanceof HTMLInputElement)
            || !(message instanceof HTMLElement)
            || !(matchesList instanceof HTMLUListElement)
            || !endpoint
            || !registerUrl
        ) {
            return;
        }

        const nationalId = nationalIdInput.value.trim();
        const name = nameInput.value.trim();
        const dateOfBirth = dateOfBirthInput.value;

        if (!nationalId && (!name || !dateOfBirth)) {
            duplicateCheck.classList.add('hidden');
            clearMatches();
            return;
        }

        debounceTimer = window.setTimeout(async () => {
            activeRequest = new AbortController();
            const query = new URLSearchParams();

            if (nationalId) {
                query.set('national_id', nationalId);
            }

            if (name) {
                query.set('name', name);
            }

            if (dateOfBirth) {
                query.set('date_of_birth', dateOfBirth);
            }

            try {
                const response = await fetch(`${endpoint}?${query.toString()}`, {
                    headers: { Accept: 'application/json' },
                    signal: activeRequest.signal,
                });

                if (!response.ok) {
                    throw new Error('Pemeriksaan data pasien tidak dapat dilakukan saat ini.');
                }

                const data = await response.json();
                clearMatches();

                if (data.matches.length === 0) {
                    duplicateCheck.classList.add('hidden');
                    return;
                }

                message.textContent = 'Kemungkinan data pasien yang sama ditemukan. Periksa rekam medis sebelum menambahkan pasien baru:';

                data.matches.forEach((patient) => {
                    const item = document.createElement('li');
                    const details = document.createElement('p');
                    const link = document.createElement('a');
                    const patientBirthDate = patient.date_of_birth ? ` · ${patient.date_of_birth}` : '';
                    const matchReason = patient.matched_by.join(', ');
                    const registrationUrl = new URL(registerUrl, window.location.origin);

                    details.className = 'font-semibold';
                    details.textContent = `${patient.name}${patientBirthDate} · ${patient.sex} · cocok berdasarkan ${matchReason}`;
                    link.className = 'mt-1 inline-flex font-semibold text-clinic-800 underline decoration-clinic-300 underline-offset-2 hover:text-clinic-950';
                    link.textContent = `Gunakan rekam medis ${patient.medical_record_number}`;
                    registrationUrl.searchParams.set('patient_search', patient.medical_record_number);
                    link.href = registrationUrl.toString();
                    item.append(details, link);
                    matchesList.append(item);
                });

                duplicateCheck.classList.remove('hidden');
            } catch (error) {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }

                clearMatches();
                message.textContent = 'Pemeriksaan kemungkinan data ganda gagal. Periksa koneksi atau lanjutkan setelah memastikan data pasien.';
                duplicateCheck.classList.remove('hidden');
            }
        }, 300);
    }

    [nameInput, nationalIdInput, dateOfBirthInput].forEach((input) => {
        input?.addEventListener('input', checkForPossibleDuplicates);
    });

    checkForPossibleDuplicates();
}

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    } else {
        sidebar?.classList.add('-translate-x-full');
    }
});
