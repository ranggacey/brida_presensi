document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorDiv = document.querySelector('.alert');

    form.addEventListener('submit', function(e) {
        let messages = [];
        
        // Validasi email
        if(emailInput.value === '' || emailInput.value == null) {
            messages.push('Email harus diisi');
        } else if(!isValidEmail(emailInput.value)) {
            messages.push('Format email tidak valid');
        }
        
        // Validasi password
        if(passwordInput.value === '' || passwordInput.value == null) {
            messages.push('Password harus diisi');
        }
        
        if(messages.length > 0) {
            e.preventDefault();
            errorDiv.textContent = messages.join(', ');
            errorDiv.style.display = 'block';
        }
    });

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Efek interaktif input
    document.querySelectorAll('.inputBox input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.querySelector('i').style.height = '100%';
        });
        
        input.addEventListener('blur', function() {
            if(this.value === '') {
                this.parentNode.querySelector('i').style.height = '2px';
            }
        });
    });
});

// Untuk efek interaksi saat hover
document.querySelectorAll('.inputBox').forEach(box => {
    box.addEventListener('mouseover', () => {
        box.style.transform = 'translateY(-5px)';
        box.style.transition = 'all 0.3s ease';
    });
    
    box.addEventListener('mouseout', () => {
        box.style.transform = 'translateY(0)';
    });
});

// Efek bounce saat submit form
document.querySelector('form').addEventListener('submit', function(e) {
    if(!this.checkValidity()) {
        e.preventDefault();
        this.classList.add('shake');
        setTimeout(() => this.classList.remove('shake'), 500);
    }
});