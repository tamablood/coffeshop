// Simple Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get the profile form
    const profileForm = document.getElementById('profileForm');
    
    // Load saved data if it exists
    const savedData = localStorage.getItem('userData');
    if (savedData) {
        const userData = JSON.parse(savedData);
        document.getElementById('userId').value = userData.id || '';
        document.getElementById('firstName').value = userData.firstName || '';
        document.getElementById('lastName').value = userData.lastName || '';
        document.getElementById('email').value = userData.email || '';
        document.getElementById('createdAt').value = userData.createdAt || '';
    }

    // Handle form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const formData = {
            id: document.getElementById('userId').value,
            firstName: document.getElementById('firstName').value,
            lastName: document.getElementById('lastName').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            createdAt: document.getElementById('createdAt').value
        };

        // Save to localStorage
        localStorage.setItem('userData', JSON.stringify(formData));
        
        // Show success message
        alert('Profile saved successfully!');
    });
}); 