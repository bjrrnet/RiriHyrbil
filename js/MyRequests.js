document.addEventListener('DOMContentLoaded', function() {
    fetchRequests();
});

async function fetchRequests() {
    const tableBody = document.getElementById('requestsBody');
    
    try {
        const response = await fetch('../public/api2.php?action=getMyRequests');
        
        if (response.status === 401) {
            tableBody.innerHTML = '<tr><td colspan="4" class="error-text">Please log in to view your requests.</td></tr>';
            return;
        }

        const requests = await response.json();

        if (requests.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="no-data">No requests found.</td></tr>';
            return;
        }

        tableBody.innerHTML = '';

        requests.forEach((req, index) => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>#${index + 1}</td>
                <td>${escapeHtml(req.subject)}</td>
                <td>${escapeHtml(req.message)}</td>
                <td>${new Date(req.created_at).toLocaleDateString()}</td>
            `;
            tableBody.appendChild(row);
        });

    } catch (error) {
        console.error('Error fetching requests:', error);
        tableBody.innerHTML = '<tr><td colspan="4" class="error-text">Error loading data. Please try again later.</td></tr>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}