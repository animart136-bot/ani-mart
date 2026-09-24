const imageInput =
    document.getElementById("image-input");

if (imageInput) {

    imageInput.addEventListener(
        "change",
        function(e){

            const file =
                e.target.files[0];

            if(file){

                const reader =
                    new FileReader();

                reader.onload = function(event){

                    const preview =
                        document.getElementById(
                            "preview-image"
                        );

                    preview.src =
                        event.target.result;

                    preview.classList.remove(
                        "d-none"
                    );
                };

                reader.readAsDataURL(file);
            }
        }
    );
}
/*
|--------------------------------------------------------------------------
| Sales Chart
|--------------------------------------------------------------------------
*/
const salesChart =
    document.getElementById(
        "salesChart"
    );

if(salesChart){

    const salesData =
        window.monthlySalesData || [];

    new Chart(salesChart, {

        type: "line",

        data: {

            labels: salesData.map(
                item => "Month " + item.month
            ),

            datasets: [{

                label: "Sales",

                data: salesData.map(
                    item => item.total
                ),

                tension: 0.3,

                fill: true
            }]
        },

        options: {

            responsive: true,

            plugins: {

                legend: {
                    display: true
                }
            }
        }
    });
}
/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/
async function loadNotifications(){

    const list =
        document.getElementById(
            "notification-list"
        );

    if(!list) return;

    const response =
        await fetch(
            "/ani-mart/public/notifications/fetch"
        );

    const data =
        await response.json();

    /*
    |--------------------------------------------------------------------------
    | Update Badge
    |--------------------------------------------------------------------------
    */
    const badge =
        document.getElementById(
            "notification-count"
        );

    if(badge){

        badge.innerText =
            data.count;
    }

    /*
    |--------------------------------------------------------------------------
    | Empty State
    |--------------------------------------------------------------------------
    */
    if(
        data.notifications.length === 0
    ){

        list.innerHTML = `
            <div class="p-3 text-muted">
                No notifications.
            </div>
        `;

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Render Notifications
    |--------------------------------------------------------------------------
    */
    let html = '';

    data.notifications.forEach(item => {

        html += `
            <div class="p-3 border-bottom notification-item">

                <div class="d-flex justify-content-between">

                    <strong>
                        ${item.title}
                    </strong>

                    ${
                        item.is_read == 0
                        ?
                        `<span class="badge bg-danger">
                            New
                        </span>`
                        :
                        ''
                    }

                </div>

                <div class="small text-muted mt-1">

                    ${item.message}

                </div>

            </div>
        `;
    });

    list.innerHTML = html;
}

/*
|--------------------------------------------------------------------------
| Mark All Read
|--------------------------------------------------------------------------
*/
const markReadBtn =
    document.getElementById(
        "mark-read-btn"
    );

if(markReadBtn){

    markReadBtn.addEventListener(
        "click",
        async function(){

            await fetch(
                "/ani-mart/public/notifications/read",
                {
                    method:"POST"
                }
            );

            loadNotifications();
        }
    );
}

/*
|--------------------------------------------------------------------------
| Poll Notifications
|--------------------------------------------------------------------------
*/
setInterval(() => {

    loadNotifications();

}, 10000);

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/
loadNotifications();