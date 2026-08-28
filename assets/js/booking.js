// Booking JavaScript

function checkAvailability() {
    const vehicleId = document.getElementById('vehicle_id').value;
    const pickup = document.getElementById('pickup_datetime').value;
    const return_ = document.getElementById('return_datetime').value;
    
    if (!vehicleId || !pickup || !return_) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please select vehicle, pickup date, and return date.'
        });
        return;
    }
    
    const btn = document.getElementById('checkBtn');
    btn.innerHTML = '<span class="spinner spinner-sm"></span> Checking...';
    btn.disabled = true;
    
    fetch('/api/check-availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            vehicle_id: vehicleId,
            pickup_datetime: pickup,
            return_datetime: return_
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = 'Check Availability';
        btn.disabled = false;
        
        if (data.available) {
            Swal.fire({
                icon: 'success',
                title: 'Available!',
                text: 'This vehicle is available for the selected dates.',
                confirmButtonColor: '#8B0000'
            });
            document.getElementById('bookingForm').style.display = 'block';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Not Available',
                text: 'This vehicle is already booked for the selected dates. Please choose different dates.',
                confirmButtonColor: '#8B0000'
            });
            document.getElementById('bookingForm').style.display = 'none';
        }
    })
    .catch(error => {
        btn.innerHTML = 'Check Availability';
        btn.disabled = false;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to check availability. Please try again.',
            confirmButtonColor: '#8B0000'
        });
    });
}

function calculateRental() {
    const pickup = document.getElementById('pickup_datetime').value;
    const return_ = document.getElementById('return_datetime').value;
    const pricePerDay = parseFloat(document.getElementById('price_per_day').value) || 0;
    
    if (pickup && return_ && pricePerDay > 0) {
        const start = new Date(pickup);
        const end = new Date(return_);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 0) {
            document.getElementById('rental_days').value = diffDays;
            document.getElementById('vehicle_price').value = (diffDays * pricePerDay).toFixed(2);
            updateTotal();
        }
    }
}

function updateTotal() {
    const vehiclePrice = parseFloat(document.getElementById('vehicle_price').value) || 0;
    const additionalCost = parseFloat(document.getElementById('additional_cost').value) || 0;
    const total = vehiclePrice + additionalCost;
    document.getElementById('total_amount').value = total.toFixed(2);
}

function submitPayment() {
    const proof = document.getElementById('payment_proof');
    if (proof.files.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Proof',
            text: 'Please upload proof of payment.',
            confirmButtonColor: '#8B0000'
        });
        return false;
    }
    
    const btn = document.querySelector('#paymentForm button[type="submit"]');
    btn.innerHTML = '<span class="spinner spinner-sm"></span> Processing...';
    btn.disabled = true;
    
    return true;
}
