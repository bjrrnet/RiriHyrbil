async function loadRequests() {
            const container = document.getElementById('requests-list');
            try {
                const response = await fetch('../api.php?action=getMyRequests');
                const data = await response.json();

                if (response.status === 401) {
                    container.innerHTML = "<p>Please <a href='../html/login.html'>Login</a> to see your requests.</p>";
                    return;
                }

                if (data.length === 0) {
                    container.innerHTML = "<p>You haven't submitted any requests yet.</p>";
                    return;
                }

                container.innerHTML = ''; 
                data.forEach(req => {
                    const div = document.createElement('div');
                    div.className = 'request-item';
                    div.innerHTML = `
                        <div class="request-subject">${req.subject}</div>
                        <div class="request-message">${req.message}</div>
                        <div class="request-date">${new Date(req.created_at).toLocaleString()}</div>
                    `;
                    container.appendChild(div);
                });
            } catch (error) {
                container.innerHTML = "<p>Error loading requests. Please try again.</p>";
            }
        }

    window.onload = loadRequests;