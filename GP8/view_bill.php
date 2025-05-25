<?php
session_start();
date_default_timezone_set('Australia/Sydney'); // Set time zone for NSW, Australia
if (!isset($_SESSION["user"])) {
    header("Location: user_login.php");
    exit();
}

require 'lib/fpdf.php';

$conn = new mysqli("localhost", "root", "", "hms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET["bill_no"])) {
    die("Bill number not provided.");
}

$bill_no = $_GET["bill_no"];

// Fetch bill details
$stmt = $conn->prepare("SELECT b.bill_no, b.appt_id, b.doctor_charge, b.total_bill, b.bill_date, 
                               a.pid, a.doctorid, a.appt_date, a.reason 
                        FROM bill b 
                        JOIN appointment a ON b.appt_id = a.appt_id 
                        WHERE b.bill_no = ?");
$stmt->bind_param("s", $bill_no);
$stmt->execute();
$bill_result = $stmt->get_result();
$bill = $bill_result->fetch_assoc();
$stmt->close();

if (!$bill) {
    die("Bill not found.");
}

// Fetch patient details
$stmt = $conn->prepare("SELECT name, dob, gender, phoneno FROM patient WHERE pid = ?");
$stmt->bind_param("i", $bill["pid"]);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result->fetch_assoc();
$stmt->close();

// Fetch doctor details
$stmt = $conn->prepare("SELECT doctorname, dept FROM doctor WHERE doctorid = ?");
$stmt->bind_param("s", $bill["doctorid"]);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor = $doctor_result->fetch_assoc();
$stmt->close();

// Get current time using DateTime
$current_time = new DateTime('now', new DateTimeZone('Australia/Sydney'));
$formatted_time = $current_time->format('H:i:s'); // e.g., 14:30:45
$formatted_date = (new DateTime($bill["bill_date"]))->format('Y-m-d'); // Ensure date-only (e.g., 2025-04-22)
$formatted_datetime = $formatted_date . ' ' . $formatted_time; // e.g., 2025-04-22 14:30:45

// Handle PDF generation
if (isset($_GET["download"])) {
    try {
        $pdf = new FPDF();
        $pdf->AddPage();

        // Add Hospital Logo
        $pdf->Image('images/new_logo.jpeg', 10, 10, 40);
        // Add NSW Health Logo
        $pdf->Image('images/health.jpeg', 160, 10, 40);

        // Title
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Ln(35);
        $pdf->Cell(0, 10, 'Hospital Bill', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, "Bill No: {$bill['bill_no']} | Appt ID: {$bill['appt_id']} | Date & Time: {$formatted_datetime}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Patient:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "{$patient['name']} | DOB: {$patient['dob']} | {$patient['gender']} | Phone: {$patient['phoneno']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Doctor:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "{$doctor['doctorname']} | Dept: {$doctor['dept']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Appointment:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "Date: {$bill['appt_date']} | Reason: {$bill['reason']}", 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Payment:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "Doctor Charge: $" . number_format($bill['doctor_charge'], 2), 0, 1);
        $pdf->Cell(0, 6, "Total (incl. 10% GST): $" . number_format($bill['total_bill'], 2), 0, 1);

        ob_clean();
        $pdf->Output('D', "bill_{$bill_no}.pdf");
        exit();
    } catch (Exception $e) {
        die("PDF generation failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>View Bill - Hospital Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            background-color: #fff; /* Browser default white, matching index.php */
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .logo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .logo-header img {
            width: 120px;
            height: auto;
        }
        h1 {
            text-align: center;
            color: #3498db;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .bill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .bill-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .bill-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .bill-item h2 {
            color: #3498db;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .bill-item p {
            margin: 6px 0;
            font-size: 14px;
            color: #333;
        }
        .payment-section {
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
            margin-bottom: 20px;
        }
        .payment-section h2 {
            color: #3498db;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .payment-section select, 
        .payment-section input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .payment-section select:focus,
        .payment-section input[type="text"]:focus {
            border-color: #3498db;
            outline: none;
        }
        .payment-section .payment-logos {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            justify-content: center;
        }
        .payment-section .payment-logos img {
            width: 50px;
            height: auto;
            transition: transform 0.2s ease;
        }
        .payment-section .payment-logos img:hover {
            transform: scale(1.1);
        }
        .payment-section .consent {
            margin: 15px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .payment-section button {
            background: linear-gradient(90deg, #28a745, #34c759);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .payment-section button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .payment-section button:hover:not(:disabled) {
            background: linear-gradient(90deg, #218838, #2db84d);
            transform: translateY(-2px);
        }
        .actions {
            text-align: center;
            margin-bottom: 20px;
        }
        .actions a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .actions a:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        .back-link {
            text-align: center;
        }
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        @media print {
            .payment-section {
                display: none;
            }
            .actions a[href*="download"] {
                display: none;
            }
            .back-link {
                display: none;
            }
        }
        @media (max-width: 600px) {
            .bill-grid {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 15px;
            }
            .logo-header img {
                width: 100px;
            }
        }
    </style>
    <script>
        function togglePayButton() {
            const consent = document.getElementById('consent');
            const payButton = document.querySelector('.payment-section button');
            payButton.disabled = !consent.checked;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="logo-header">
            <img src="images/new_logo.jpeg" alt="Hospital Logo">
            <img src="images/health.jpeg" alt="NSW Health Logo">
        </div>
        <h1>Bill #<?php echo htmlspecialchars($bill["bill_no"]); ?></h1>

        <div class="bill-grid">
            <div class="bill-item">
                <h2>Patient</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient["name"]); ?></p>
                <p><strong>DOB:</strong> <?php echo htmlspecialchars($patient["dob"]); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient["gender"]); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient["phoneno"]); ?></p>
            </div>
            <div class="bill-item">
                <h2>Doctor</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($doctor["doctorname"]); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($doctor["dept"]); ?></p>
            </div>
            <div class="bill-item">
                <h2>Appointment</h2>
                <p><strong>ID:</strong> <?php echo htmlspecialchars($bill["appt_id"]); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($bill["appt_date"]); ?></p>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($bill["reason"]); ?></p>
            </div>
            <div class="bill-item">
                <h2>Bill Details</h2>
                <p><strong>Date & Time:</strong> <?php echo htmlspecialchars($formatted_datetime); ?></p>
                <p><strong>Doctor Charge:</strong> $<?php echo number_format($bill["doctor_charge"], 2); ?></p>
                <p><strong>Total (incl. 10% GST):</strong> $<?php echo number_format($bill["total_bill"], 2); ?></p>
            </div>
        </div>

        <div class="payment-section">
            <h2>Make Payment</h2>
            <form method="POST" action="process_payment.php">
                <input type="hidden" name="bill_no" value="<?php echo htmlspecialchars($bill["bill_no"]); ?>">
                <select name="payment_method" required>
                    <option value="">Select Method</option>
                    <option value="mastercard">MasterCard</option>
                    <option value="visa">Visa</option>
                    <option value="paypal">PayPal</option>
                </select>
                <div class="payment-logos">
                    <img src="images/mastercard.png" alt="MasterCard Logo">
                    <img src="images/visa.png" alt="Visa Logo">
                    <img src="images/logo-paypal.png" alt="PayPal Logo">
                </div>
                <input type="text" name="card_number" placeholder="Card Number" required>
                <input type="text" name="expiry" placeholder="MM/YY" required>
                <input type="text" name="cvc" placeholder="CVC" required>
                <div class="consent">
                    <input type="checkbox" id="consent" onchange="togglePayButton()">
                    <label for="consent">I consent to process this payment</label>
                </div>
                <button type="submit" disabled>Pay Now</button>
            </form>
        </div>

        <div class="actions">
            <a href="view_bill.php?bill_no=<?php echo htmlspecialchars($bill["bill_no"]); ?>&download=1">Download PDF</a>
            <a href="javascript:window.print()">Print</a>
        </div>

        <div class="back-link">
            <a href="patient_dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>