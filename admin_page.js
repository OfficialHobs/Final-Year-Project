document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Navigation
    const sidebarItems = document.querySelectorAll('.sidebar ul li');
    const contents = document.querySelectorAll('.content');
    const dashboardCards = document.querySelectorAll('.card');

    sidebarItems.forEach((item) => {
        item.addEventListener('click', () => {
            sidebarItems.forEach(i => i.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            item.classList.add('active');
            const contentId = item.id + '-content';
            document.getElementById(contentId).classList.add('active');
        });
    });

    dashboardCards.forEach(card => {
        card.addEventListener('click', () => {
            const target = card.getAttribute('data-target');
            sidebarItems.forEach(i => i.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            document.getElementById(target).classList.add('active');
            document.getElementById(target.replace('-content', '')).classList.add('active');
        });
    });

    // Dropdown for Logout
    const userInfo = document.querySelector('.user-info');
    const dropdownContent = document.querySelector('.dropdown-content');
    const dropdownArrow = document.querySelector('.dropdown-arrow');

    dropdownArrow.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!userInfo.contains(e.target)) {
            dropdownContent.style.display = 'none';
        }
    });

    // Edit Profile Functionality
    const editProfileBtn = document.getElementById('edit-profile-btn');
    const saveChangesBtn = document.getElementById('save-changes-btn');
    const editableProfileFields = document.querySelectorAll('#first_name, #last_name, #email_address, #phone_number');

    editProfileBtn.addEventListener('click', function() {
        editableProfileFields.forEach(field => {
            field.removeAttribute('readonly');
            field.removeAttribute('disabled');
            field.style.backgroundColor = "#ffffff";
            field.style.cursor = "text";
        });

        editProfileBtn.style.display = 'none';
        saveChangesBtn.style.display = 'block';
    });

    // Image Upload and Preview Functionality in Profile Section
    const uploadImageBtn = document.getElementById('upload-image-btn');
    const profileImageInput = document.getElementById('profile_image');
    const imagePreview = document.getElementById('image-preview');

    uploadImageBtn.addEventListener('click', function() {
        profileImageInput.click();
    });

    profileImageInput.addEventListener('change', function() {
        const file = profileImageInput.files[0];
        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                imagePreview.src = e.target.result;
            };

            reader.readAsDataURL(file);
        }
    });

    // Edit Hostel Account Functionality
    const editHostelBtn = document.getElementById('edit-hostel-btn');
    const saveHostelChangesBtn = document.getElementById('save-hostel-changes-btn');
    const editableHostelFields = document.querySelectorAll('#institution_name, #institution_type, #country, #state, #lga, #address, #opening_time, #closing_time, #hostel_name, #hostel_type, #hostel_address, #student_type, #hostel_email, #emergency_contact, #hostel_division, #total_rooms');

    editHostelBtn.addEventListener('click', function() {
        editableHostelFields.forEach(field => {
            field.removeAttribute('readonly');
            field.removeAttribute('disabled');
            field.style.backgroundColor = "#ffffff";
            field.style.cursor = "text";
        });

        editHostelBtn.style.display = 'none';
        saveHostelChangesBtn.style.display = 'block';
    });

// View Profile in User Table (Room Settings)
const viewProfileButtons = document.querySelectorAll('.view-profile-btn');
const profileModal = document.getElementById('profile-modal');
const profileDetails = document.getElementById('profile-details');
const closeModalBtn = document.getElementById('close-modal-btn');
const receiptModal = document.getElementById('receipt-modal');
const receiptImage = document.getElementById('receipt-image');
const closeReceiptBtn = document.getElementById('close-receipt-btn');

viewProfileButtons.forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.getAttribute('data-id');
        console.log('Fetching profile for user ID:', userId); // Debug log

        fetch(`get_user_profile.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error); // Handle error
                } else {
                    // Populate profile details with fetched user data
                    profileDetails.innerHTML = `
                        <p><strong>Full Name:</strong> ${data.full_name}</p>
                        <p><strong>Matric Number:</strong> ${data.matric_number}</p>
                        <p><strong>Department:</strong> ${data.department}</p>
                        <p><strong>Faculty:</strong> ${data.faculty}</p>
                        <p><strong>Gender:</strong> ${data.gender}</p>
                        <p><strong>Phone Number:</strong> ${data.phone_number}</p>
                        <p><strong>Address:</strong> ${data.address}</p>
                        <p><strong>Email Address:</strong> ${data.email_address}</p>
                        <p><strong>Guardian Name:</strong> ${data.guardian_name}</p>
                        <p><strong>Guardian Phone:</strong> ${data.guardian_phone_number}</p>
                    `;

                    // Check if the user has a receipt image
                    if (data.receipt_image) {
                        console.log('Receipt Image Found:', data.receipt_image); // Debug log
                        profileDetails.innerHTML += `
                            <button id="view-receipt-btn">View Receipt</button>
                        `;

                        const viewReceiptBtn = document.getElementById('view-receipt-btn');
                        viewReceiptBtn.addEventListener('click', () => {
                            const loadingSpinner = document.getElementById('loading-spinner');

                            // Show spinner, hide image initially
                            loadingSpinner.style.display = 'block';
                            receiptImage.style.display = 'none';

                            // Set the image source
                            receiptImage.src = `uploads/receipts/${data.receipt_image}`;

                            // Add an event listener to hide the spinner once the image loads
                            receiptImage.onload = function() {
                                loadingSpinner.style.display = 'none'; // Hide the spinner
                                receiptImage.style.display = 'block';  // Show the image
                            };

                            receiptModal.style.display = 'block'; // Show the receipt modal
                            setupZoomControls(); // Setup zoom controls after opening modal
                        });
                    } else {
                        profileDetails.innerHTML += `<p>No receipt uploaded.</p>`;
                    }

                    profileModal.style.display = 'block'; // Show the main modal
                }
            })
            .catch(error => console.error('Error:', error));
    });
});

// Function to set up zoom controls
function setupZoomControls() {
    let scale = 1; // Initial zoom scale
    const zoomInBtn = document.getElementById('zoom-in-btn');
    const zoomOutBtn = document.getElementById('zoom-out-btn');
    const resetZoomBtn = document.getElementById('reset-zoom-btn');

    // Check if zoom buttons exist
    if (!zoomInBtn || !zoomOutBtn || !resetZoomBtn || !receiptImage) {
        console.error('Zoom buttons or image not found in the DOM'); // Debug log
        return;
    }

    // Zoom In
    zoomInBtn.addEventListener('click', function() {
        scale += 0.1; // Increase zoom by 0.1
        receiptImage.style.transform = `scale(${scale})`;
    });

    // Zoom Out
    zoomOutBtn.addEventListener('click', function() {
        if (scale > 0.2) { // Prevent zooming out too much
            scale -= 0.1;
            receiptImage.style.transform = `scale(${scale})`;
        }
    });

    // Reset Zoom
    resetZoomBtn.addEventListener('click', function() {
        scale = 1; // Reset scale to default
        receiptImage.style.transform = 'scale(1)';
    });
}

// Close Profile Modal Button
closeModalBtn.addEventListener('click', function() {
    profileModal.style.display = 'none'; // Hide modal
});

// Close Receipt Modal Button
closeReceiptBtn.addEventListener('click', function() {
    receiptModal.style.display = 'none'; // Hide receipt modal
});


    // Delete User Button
    const deleteUserButtons = document.querySelectorAll('.delete-user-btn');
    deleteUserButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this user?')) {
                fetch(`delete_user.php?id=${userId}`, {
                    method: 'DELETE'
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    window.location.reload(); // Refresh page after deletion
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });

    // Room Assignment Form Submission (AJAX)
    const roomSettingsForm = document.getElementById('room-settings-form');
    
    roomSettingsForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent form from traditional submission

        // Collect form data
        const formData = new FormData(roomSettingsForm);

        // Convert form data to a JSON object
        const data = {};
        formData.forEach((value, key) => {
            if (key.includes('room_assignment')) {
                const userId = key.match(/\d+/)[0];  // Extract user_id from room_assignment[]
                data.roomAssignments = data.roomAssignments || {};
                data.roomAssignments[userId] = value;
            } else {
                data[key] = value;
            }
        });

        // Validate input (you can add more detailed validation)
        const totalMaleRooms = parseInt(data.total_male_rooms);
        const totalFemaleRooms = parseInt(data.total_female_rooms);
        const totalRooms = parseInt(data.total_rooms);
        if (totalMaleRooms + totalFemaleRooms > totalRooms) {
            alert('Total male and female rooms exceed total rooms available!');
            return;
        }
        console.log("Data to be sent:", data); 
        // Send the data via AJAX
        fetch('save_room_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log("Response from server:", data); 
            if (data.success) {
                alert('Room settings saved successfully!');
                window.location.reload(); // Optionally reload the page after saving
            } else {
                alert('Error saving room settings: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    
    // Ensure buttons and fields are correctly selected
    const editEventBtn = document.querySelector('#edit-event-btn');
    const saveEventChangesBtn = document.querySelector('#save-event-changes-btn');
    const nameOfHostelInput = document.querySelector('#hostel-name');
    const curfewInput = document.querySelector('#curfew-time');
    const weekdaysInputs = document.querySelectorAll('#part-two input[type="text"]');
    const descriptionTextarea = document.querySelector('#description');
    const clearDescriptionBtn = document.querySelector('#clear-description-btn');
    const uploadImageInput = document.querySelector('#image-upload');
    const imagePlaceholder = document.querySelector('#image-placeholder'); 

    // Log the edit button to confirm it is found
    if (editEventBtn) {
        console.log('Edit button found');
    } else {
        console.error('Edit button not found');
    }

    // Initially disable all fields except "name of hostel"
    nameOfHostelInput.readOnly = true;
    curfewInput.disabled = true;
    weekdaysInputs.forEach(input => input.disabled = true);
    descriptionTextarea.disabled = true;
    clearDescriptionBtn.style.display = 'none';
    uploadImageInput.disabled = true;
    saveEventChangesBtn.style.display = 'none'; // Hide "Save Changes" initially
    
    // Edit button logic to enable fields for editing
    editEventBtn.addEventListener('click', (e) => {
        e.preventDefault();  // Prevent default form submission behavior
        console.log('Edit button clicked');
    
        // Enable all relevant fields for editing
        curfewInput.disabled = false;
        weekdaysInputs.forEach(input => input.disabled = false);
        descriptionTextarea.disabled = false;
        clearDescriptionBtn.style.display = 'inline';
        uploadImageInput.disabled = false;
        
        // Switch button visibility: hide "Edit", show "Save Changes"
        editEventBtn.style.display = 'none';
        saveEventChangesBtn.style.display = 'inline'; // Show "Save Changes"
    });
    
    // Save Changes button logic to submit form data via traditional form submission
    saveEventChangesBtn.addEventListener('click', () => {
        console.log('Save changes button clicked');
        const form = document.querySelector('#event-form');
        form.submit(); // Submit the form to save_event.php
    });
    
    // Clear description button logic
    clearDescriptionBtn.addEventListener('click', () => {
        descriptionTextarea.value = ''; // Clear the textarea content
        console.log('Description cleared');
    });
    
    // Image upload logic
    uploadImageInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePlaceholder.src = e.target.result; // Preview the image
                console.log('Image uploaded');
            };
            reader.readAsDataURL(file); // Convert the image file to a base64 string
        }
    });
    
    // Fetch hostel name and display in the "name of hostel" input field
    fetch('get_hostel_name.php')
        .then(response => response.json())
        .then(data => {
            if (data.hostel_name) {
                nameOfHostelInput.value = data.hostel_name;
                console.log('Hostel name loaded');
            } else {
                alert('Error fetching hostel name');
            }
        })
        .catch(error => console.error('Error:', error));


     // Ensure modals are hidden on page load
document.getElementById('viewReportModal').style.display = 'none';
document.getElementById('respondReportModal').style.display = 'none';

// Open Modal function
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    modal.style.display = 'flex'; // Use flex to center it
}

// Close Modal function
function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    modal.style.display = 'none';
}

// Expose the functions globally
window.openModal = openModal;
window.closeModal = closeModal;

// Fetch and display report details in the View Report modal
window.viewReport = function(reportId) {
    // Fetch the report details via AJAX
    fetch('get_report.php?id=' + reportId)
        .then(response => response.json())
        .then(data => {
            // Update the student's report details
            document.getElementById('reportType').textContent = data.problem_type;
            document.getElementById('reportDescription').textContent = data.description;

            // Update the admin's response if available
            if (data.admin_response) {
                document.getElementById('adminResponseText').textContent = data.admin_response;
            } else {
                document.getElementById('adminResponseText').textContent = 'No response yet.';
            }

            // Open the modal
            openModal('viewReportModal');
        })
        .catch(error => {
            console.error('Error fetching report details:', error);
        });
};

// Prepare the Respond Report modal with the report ID
window.respondReport = function(reportId) {
    document.getElementById('reportId').value = reportId;
    openModal('respondReportModal');
};

});

