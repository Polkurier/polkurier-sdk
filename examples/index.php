<?php
$requestMethod = $_SERVER["REQUEST_METHOD"];
$requestUri = $_SERVER['REQUEST_URI'];
require_once 'src/App.php';
$app = new \App();

$routes = [
    '/api' => [
        'action' => function() use ($app) {
            // Pobierz nazwę metody z parametru POST
            $methodName = $_POST['method'];
            $params = isset($_POST['params']) ? json_decode($_POST['params'], true) : [];

            // Wykonaj metodę za pomocą App::run
            if ($methodName) {
                $response = $app->run($methodName, $params);
                header('Content-Type: application/json');
                return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            return json_encode(['error' => 'Nieprawidłowa metoda'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    ],
    '/availableCarriers' => [
        'label' => 'Available Carriers',
        'name' => $app::GET_AVAILABLE_CARRIERS_METHOD
    ],
    '/healthBeat' => [
        'label' => 'Health Beat',
        'name' => $app::HEALTH_BEAT_METHOD
    ],
    '/testAuthApi' => [
        'label' => 'Test Auth API',
        'name' => $app::TEST_AUTH_API_METHOD
    ],
    '/supportedCountriesV2' => [
        'label' => 'Supported Countries V2',
        'name' => $app::GET_SUPPORTED_COUNTRIES_V2_METHOD
    ],
    '/courierPickupTime' => [
        'label' => 'Courier Pickup Time',
        'name' => $app::GET_COURIER_PICKUP_TIME_METHOD
    ],
    '/courierPoints' => [
        'label' => 'Courier Points',
        'name' => $app::GET_COURIER_POINTS_METHOD
    ],
    '/isMultiPackAvailable' => [
        'label' => 'Is Multi Pack Available',
        'name' => $app::IS_MULTI_PACK_AVAILABLE_METHOD
    ],
    '/createMultiOrderPickup' => [
        'label' => 'Create Multi Order Pickup',
        'name' => $app::CREATE_MULTI_ORDER_PICKUP_METHOD
    ],
    '/getAddresses' => [
        'label' => 'Get Addresses',
        'name' => $app::GET_ADDRESSES_METHOD
    ],
    '/deleteAddresses' => [
        'label' => 'Delete Address',
        'name' => $app::DELETE_ADDRESS_METHOD
    ],
    '/getBankAccounts' => [
        'label' => 'Get Bank Accounts',
        'name' => $app::GET_BANK_ACCOUNTS_METHOD
    ],
    '/saveBankAccount' => [
        'label' => 'Save Bank Account',
        'name' => $app::SAVE_BANK_ACCOUNT_METHOD
    ],
    '/deleteBankAccount' => [
        'label' => 'Delete Bank Account',
        'name' => $app::DELETE_BANK_ACCOUNT_METHOD
    ],
    '/getPackTemplates' => [
        'label' => 'Get Pack Templates',
        'name' => $app::GET_PACK_TEMPLATES_METHOD
    ],
    '/savePackTemplate' => [
        'label' => 'Save Pack Template',
        'name' => $app::SAVE_PACK_TEMPLATES_METHOD
    ],
    '/deletePackTemplate' => [
        'label' => 'Delete Pack Template',
        'name' => $app::DELETE_PACK_TEMPLATES_METHOD
    ],
    '/getMapToken' => [
        'label' => 'Get Map Token',
        'name' => $app::GET_MAP_TOKEN_METHOD
    ],
    '/getOrders' => [
        'label' => 'Get Orders',
        'name' => $app::GET_ORDERS_METHOD
    ],
    '/createOrder' => [
        'label' => 'Create Order',
        'name' => $app::CREATE_ORDER_METHOD
    ],
    '/orderDetails' => [
        'label' => 'Get Order Details',
        'name' => $app::GET_SINGLE_ORDER
    ],
    '/saveAddress' => [
        'label' => 'Save Address',
        'name' => $app::SAVE_ADDRESS_METHOD
    ],
    '/getOrderValuation' => [
        'label' => 'Order Valuation',
        'name' => $app::ORDER_VALUATION_METHOD
    ]
];

function handleRequest($routes, $requestUri) {
    foreach ($routes as $route => $info) {
        $pattern = preg_replace('/\{[^\}]+\}/', '([^\/]+)', $route);
        if (preg_match('@^' . $pattern . '$@', $requestUri, $matches)) {
            array_shift($matches);
            return call_user_func_array($info['action'], $matches);
        }
    }
    return '404 Not Found';
}

$result = handleRequest($routes, $requestUri);
$carriers = $app->run($app::GET_AVAILABLE_CARRIERS_METHOD);
//echo '<pre>';
//var_dump($carriers);

if ($requestUri === '/api') {
    echo $result; // Wyświetlenie JSON dla ścieżki /api
} else {
    // Wyświetlenie HTML dla pozostałych ścieżek
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Polkurier API SDK Test Page</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                background-color: #f0f0f0;
            }
            header {
                background-color: #333;
                color: #fff;
                padding: 15px;
                width: 100%;
                text-align: center;
            }
            main {
                display: flex;
                width: 100%;
                max-width: 1200px;
                margin: 20px auto;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                background-color: #fff;
            }
            .methods, .result {
                flex: 1;
                padding: 20px;
                box-sizing: border-box;
            }
            .methods {
                max-width: 35%;
                border-right: 1px solid #ccc;
            }
            .result {
                max-width: 65%;
                padding-left: 20px;
                word-wrap: break-word;
                overflow: hidden;
            }
            code {
                display: block;
                background-color: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
                white-space: pre-wrap;
                font-size: 14px;
                border: 1px solid #ddd;
            }
            ul {
                list-style-type: none;
                padding: 0;
                margin: 0;
            }
            li {
                margin: 10px 0;
            }
            a {
                color: #007BFF;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
            h2 {
                margin-top: 0;
            }
            .methods ul {
                max-height: 500px;
                overflow-y: auto;
                padding-right: 10px;
            }
            .result pre {
                max-height: 500px; /* Maksymalna wysokość dla sekcji z wynikiem */
                overflow-y: auto; /* Przewijanie pionowe */
                white-space: pre-wrap; /* Zapobiega zawijaniu wierszy */
                background-color: #f9f9f9;
            }
            .send-button {
                margin-top: 5px;
            }
        </style>
    </head>
    <body>
    <header>
        <h1>Polkurier API SDK Test Page</h1>
    </header>

    <main>
        <section class="methods">
            <h2>Test API Methods</h2>
            <ul>
                <?php foreach ($routes as $path => $info): ?>
                    <?php if (isset($info['label'])): ?>
                        <li><a href="#<?= $info['name']; ?>" data-method="<?= $info['name']; ?>"><?= $info['label']; ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="result">
            <h2>Method: </h2>
            <form id="courierPickupTimeForm" style="display:none;">
                <label for="courier">Wybierz przewoźnika:</label>
                <select name="courier" id="courier">
                    <?php foreach ($carriers as $carrier): ?>
                        <option value="<?= $carrier->getServiceCode() ?>"><?= $carrier->getName() ?></option>
                    <?php endforeach; ?>
                </select>
                <br><br>
                <label for="shipFrom">Kod pocztowy nadawcy:</label>
                <input type="text" name="shipFrom" id="shipFrom" value="63-400" required>
                <br><br>
                <label for="shipTo">Kod pocztowy odbiorcy:</label>
                <input type="text" name="shipTo" id="shipTo" value="01-234" required>
                <br><br>
                <label for="parcel">Rodzaj przesyłki:</label>
                <select name="parcel" id="parcel">
                    <option value="envelope">Koperta</option>
                    <option value="box" selected>Paczka</option>
                    <option value="palette">Paleta</option>
                </select>
                <br><br>
            </form>
            <button id="sendRequest" class="send-button" style="display:none;">
                Wyślij
            </button>
            <pre><code></code></pre>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var methodLinks = document.querySelectorAll('.methods a[data-method]');
            var sendButton = document.getElementById('sendRequest');
            var resultContainer = document.querySelector('.result code');
            var selectedMethod = ''; // Przechowuje aktualnie wybraną metodę
            var courierPickupTimeForm = document.getElementById('courierPickupTimeForm');

            // Ustawienie obsługi zdarzeń dla linków metod
            methodLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    selectedMethod = link.getAttribute('data-method'); // Pobierz nazwę metody
                    document.querySelector('.result h2').textContent = 'Result for method: ' + selectedMethod;
                    sendButton.style.display = 'inline-block'; // Pokaż przycisk "Wyślij"
                    resultContainer.textContent = ''; // Wyczyść pole wyników przy zmianie metody
                    if (selectedMethod === 'courierPickupTime') {
                        courierPickupTimeForm.style.display = 'block';
                    } else {
                        courierPickupTimeForm.style.display = 'none';
                    }
                });
            });

            // Obsługa kliknięcia przycisku "Wyślij"
            sendButton.addEventListener('click', function () {
                if (selectedMethod) {
                    resultContainer.textContent = ''; // Wyczyść pole wyników przed wysłaniem zapytania

                    var params = new URLSearchParams();
                    if (selectedMethod === 'courierPickupTime') {
                        // Pobierz dane z formularza
                        params.append('courier', document.getElementById('courier').value);
                        params.append('shipFrom', document.getElementById('shipFrom').value);
                        params.append('shipTo', document.getElementById('shipTo').value);
                        params.append('parcel', document.getElementById('parcel').value);
                    }

                    fetch('/api', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'method=' + encodeURIComponent(selectedMethod) + '&' + params.toString()
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(function (data) {
                            resultContainer.textContent = JSON.stringify(data, null, 4);
                        })
                        .catch(function (error) {
                            resultContainer.textContent = 'Wystąpił błąd podczas wysyłania zapytania: ' + error.message;
                        });
                }
            });
        });
    </script>
    </body>
    </html>
    <?php
}
?>
