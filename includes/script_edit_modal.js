

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        // Collect all form data including checkboxes
        const formData = new FormData(this);
        
        // Manually append checkboxes (alternative approach)
        const genders = [];
        document.querySelectorAll('input[name="gender[]"]:checked').forEach(cb => {
            genders.push(cb.value);
        });
        formData.set('gender', genders.join(','));
        
        const sizes = [];
        document.querySelectorAll('input[name="size[]"]:checked').forEach(cb => {
            sizes.push(cb.value);
        });
        formData.set('size', sizes.join(','));
        
        const response = await fetch("update_salary.php", {
            method: "POST",
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        alert(result.message);
        if (result.reload) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
        alert("Error: " + error.message);
    }
});





