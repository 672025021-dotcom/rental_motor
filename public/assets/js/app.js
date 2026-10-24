document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.querySelector('#booking-form');
    if (!bookingForm) {
        return;
    }

    const startDate = bookingForm.querySelector('#start_date');
    const endDate = bookingForm.querySelector('#end_date');
    const deliveryGroup = bookingForm.querySelector('#delivery-address-group');
    const deliveryAddress = bookingForm.querySelector('#delivery_address');
    const totalDays = bookingForm.querySelector('#total-days');
    const totalPrice = bookingForm.querySelector('#total-price');
    const pricePerDay = Number(document.querySelector('#price-per-day')?.dataset.pricePerDay ?? 0);
    const dateFormatter = new Intl.NumberFormat('id-ID');
    const rupiahFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    });

    const updateEstimate = () => {
        const start = startDate.value ? Date.parse(`${startDate.value}T00:00:00Z`) : Number.NaN;
        const end = endDate.value ? Date.parse(`${endDate.value}T00:00:00Z`) : Number.NaN;

        if (!Number.isFinite(start) || !Number.isFinite(end)) {
            totalDays.textContent = '-';
            totalPrice.textContent = '-';
            return;
        }

        const days = Math.floor((end - start) / 86400000);
        if (days < 0) {
            totalDays.textContent = 'Tanggal selesai harus setelah tanggal mulai';
            totalPrice.textContent = '-';
            endDate.setCustomValidity('Tanggal selesai tidak boleh sebelum tanggal mulai.');
            return;
        }

        endDate.setCustomValidity('');
        totalDays.textContent = `${dateFormatter.format(days)} hari`;
        totalPrice.textContent = rupiahFormatter.format(days * pricePerDay);
    };

    const updateDeliveryAddress = () => {
        const selectedMethod = bookingForm.querySelector('input[name="pickup_method"]:checked');
        const requiresDeliveryAddress = selectedMethod?.value === 'diantar';
        deliveryGroup.classList.toggle('d-none', !requiresDeliveryAddress);
        deliveryAddress.disabled = !requiresDeliveryAddress;
        deliveryAddress.required = requiresDeliveryAddress;
    };

    startDate.addEventListener('change', () => {
        endDate.min = startDate.value || startDate.min;
        if (endDate.value && endDate.value < startDate.value) {
            endDate.value = '';
        }
        updateEstimate();
    });
    endDate.addEventListener('change', updateEstimate);
    bookingForm.querySelectorAll('input[name="pickup_method"]').forEach((input) => {
        input.addEventListener('change', updateDeliveryAddress);
    });
    bookingForm.addEventListener('submit', (event) => {
        updateEstimate();
        if (!bookingForm.reportValidity()) {
            event.preventDefault();
        }
    });

    updateDeliveryAddress();
    updateEstimate();
});