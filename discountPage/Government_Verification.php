<?php
require_once "../config.php"; 
include("../auth.php");

$loginPage = "/SADPROJ/login.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "PASSENGER") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}

$user_id = $_SESSION['UserID'];

// ✅ Check if user already submitted a GOVERNMENT application
$stmt = $conn->prepare("SELECT * FROM discount_applications WHERE UserID = ? AND Category = 'Government'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$hasApplication = $result->num_rows > 0 ? true : false;
$application = $hasApplication ? $result->fetch_assoc() : null;
$stmt->close();
$conn->close();
?>

<html>
<head>
<title> Government Employee Verify </title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; }
  body::-webkit-scrollbar {
    display: none; /* Hides the scrollbar */
    width: 0; /* Ensures no width space is reserved for the scrollbar */
    }
header { background: linear-gradient(90deg, #2e7d32, #66bb6a); padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; color: white; box-shadow: 0 3px 6px rgba(0,0,0,0.15); position: sticky; top: 0; z-index: 100; }
.menu { font-size: 26px; cursor: pointer; transition: transform 0.2s; }
.menu:hover { transform: scale(1.1); }
/* SIDEBAR */
    .sidebar {
        height: 100%;
        width: 0;
        position: fixed;
        top: 0;
        left: 0;
        background-color: #1b1b1b;
        overflow: hidden;
        transition: 0.4s;
        padding-top: 60px;
        z-index: 1000;
        transition: width 0.3s ease, background 0.3s ease;
    }
    /* 1. Update padding to accommodate the icon spacing */
    .sidebar a {
        padding: 14px 28px;
        text-decoration: none;
        font-size: 18px;
        color: #ddd;
        display: block;
        transition: 0.3s;
    }

    .sidebar a i {
        width: 25px; 
        margin-right: 0px; 
        text-align: center; 
    }

    .sidebar a:hover {
        background: #2e7d32;
        color: #fff;
        padding-left: 35px; /* This is kept for the sliding hover effect */
    }

    .sidebar .closebtn {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        cursor: pointer;
        color: white;
    }
    
.profile { width: 35px; height: 35px; background-color: #2e7d32; color: white; font-size: 22px; display: flex; justify-content: center; align-items: center; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); transition: all 0.3s ease; margin-right: 10px; }
.profile:hover { background-color: #66bb6a; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

</style>
</head>

<body>
    
<header>
    <div class="menu" onclick="openNav()">☰</div>
    <div class="profile" onclick="location.href='../login.php'">👤</div>
</header>

  <div id="sidebar" class="sidebar" aria-hidden="true">
  <span class="closebtn" onclick="closeNav()">&times;</span>
  
  <a href="../passenger_dashboard.php">
    <i class="fas fa-home"></i> Homepage
  </a>
  
  <a href="../vehicle.php">
    <i class="fas fa-bus"></i> Vehicles
  </a>
  
  <a href="../ticketing/ticketing.php">
     <i class="fas fa-ticket-alt"></i> Buy Ticket
  </a>

  <a href="../buyCoin/buy_coins.php">
      <i class="fas fa-coins"></i> Buy Coins
  </a>
  
  <a href="../feedback.php">
    <i class="fas fa-comment-dots"></i> Feedback
  </a>
  
  <a href="../about.php">
    <i class="fas fa-info-circle"></i> About Us
  </a>
  
  <a href="discount_page.php">
    <i class="fas fa-percent"></i> Apply for a Discount
  </a>
</div>

<!-- MAIN CONTENT -->
<div class="bg-gray-50 min-h-screen flex items-center justify-center">
<?php if ($hasApplication): ?>
    <?php $status = ucfirst($application['Status'] ?? 'Pending'); ?>

    <?php if ($status === 'Approved'): ?>
        <div class="bg-white shadow-lg rounded-2xl p-10 w-full max-w-2xl text-center border-t-8 border-green-600">
            <h1 class="text-3xl font-bold text-green-700 mb-4">🏛️ Verified Government Employee Discount!</h1>
            <p class="text-gray-700 mb-6">Congratulations! Your government employee verification has been <span class="font-semibold text-green-700">approved</span>.</p>
            <div class="bg-green-50 border border-green-300 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-green-700 mb-2">You are now eligible for government employee fare discounts.</h2>
                <p class="text-sm text-gray-600">Show your verification QR code or ID to avail discounts on public transport.</p>
            </div>
            <div class="flex justify-center gap-4 mt-6">
                <button onclick="location.href='../passenger_dashboard.php'" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Back to Homepage</button>
            </div>
        </div>

    <?php elseif ($status === 'Rejected'): ?>
        <div class="bg-white shadow-lg rounded-2xl p-10 w-full max-w-2xl text-center border-t-8 border-red-600">
            <h1 class="text-3xl font-bold text-red-700 mb-4">❌ Application Rejected</h1>
            <p class="text-gray-700 mb-4">Unfortunately, your government employee discount application has been rejected.</p>
            <p class="text-gray-600 mb-6">You may reapply after the waiting period (<span class="font-semibold">7 days</span>).</p>
            <?php
            $submitted_at = strtotime($application['SubmittedAt']);
            $can_reapply_after = strtotime("+7 days", $submitted_at);
            $now = time();
            if ($now >= $can_reapply_after): ?>
                <form action="reapply_government.php" method="POST">
                    <input type="hidden" name="application_id" value="<?= $application['ApplicationID'] ?>">
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">Reapply Now</button>
                </form>
            <?php else: ?>
                <p class="text-sm text-gray-500 italic"> You can reapply on <strong><?= date('F j, Y', $can_reapply_after) ?></strong>. </p>
            <?php endif; ?>
            <button onclick="location.href='../passenger_dashboard.php'" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 mt-6">Back to Homepage</button>
        </div>

    <?php else: ?>
        <div class="bg-white shadow-lg rounded-2xl p-10 w-full max-w-2xl text-center border-t-8 border-yellow-500">
            <h1 class="text-3xl font-bold text-yellow-700 mb-4">Verification in Progress ⏳</h1>
            <p class="text-gray-700 mb-6">We’re reviewing your application. Expect updates within <strong>2–3 business days</strong>.</p>
            <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-6 mb-4 text-left">
                <h2 class="font-semibold text-yellow-800 mb-2">Uploaded Documents:</h2>
                <ul class="list-disc list-inside text-gray-700">
                    <li><a href="../uploads/<?= htmlspecialchars($application['ID_Front']) ?>" target="_blank">Government ID (Front)</a></li>
                    <li>Government ID (Back)</li>
                    <li>Employment Certification</li>
                </ul>
            </div>
            <p class="text-sm text-gray-500 mt-4">Status: <span class="font-semibold text-yellow-700">Pending</span></p>
            <button type="button" onclick="location.href='../passenger_dashboard.php'" class="bg-yellow-500 text-white px-8 py-2 rounded hover:bg-yellow-600 mt-5">Back to Homepage</button>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- ORIGINAL FORM -->
    <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-3xl">
        <h1 class="text-2xl font-bold text-center text-green-800 mb-3">GOVERNMENT EMPLOYEE VERIFICATION</h1>
        <p class="text-gray-600 mb-6 text-center">Please upload required documents and fill in your information. Accepted formats: JPG, PNG, PDF.</p>

        <div class="flex items-center gap-3 mb-2">
            <div id="step1Indicator" class="px-3 py-1 rounded-full bg-green-200 text-green-800 font-semibold">1</div>
            <div id="step2Indicator" class="px-3 py-1 rounded-full bg-gray-200 text-gray-600">2</div>
            <div id="step3Indicator" class="px-3 py-1 rounded-full bg-gray-200 text-gray-600">3</div>
            <div class="ml-4 text-sm text-gray-600">Step <span id="currentStep">1</span> of 3</div>
        </div>

        <form action="upload_government.php" method="POST" enctype="multipart/form-data" class="flex flex-col flex-grow">
            <!-- STEP 1 -->
            <div id="step1" class="step" style="display:block;">
                <h2 class="font-semibold text-lg mb-3">Required Documents</h2>
                <p class="text-gray-700 mb-6">Upload your Government ID (Front & Back)</p>
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div class="border border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center">
                        <label class="font-medium mb-2"> Government ID (Front) <span class="text-red-500">*</span></label>
                        <img id="previewFront" class="hidden w-40 h-40 object-cover mb-2 border rounded" />
                        <input type="file" name="idFront" class="border p-2 rounded w-full" id="idFront" accept="image/*" onchange="previewImage(event, 'previewFront')" required>
                    </div>
                    <div class="border border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center">
                        <label class="font-medium mb-2"> Government ID (Back) <span class="text-red-500">*</span></label>
                        <img id="previewBack" class="hidden w-40 h-40 object-cover mb-2 border rounded" />
                        <input type="file" name="idBack" class="border p-2 rounded w-full" id="idBack" accept="image/*" onchange="previewImage(event, 'previewBack')" required>
                    </div>
                </div>
                <div class="flex justify-between">
                    <button type="button" class="bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500" onclick="location.href='discount_page.php'">Back to Categories</button>
                    <button type="button" onclick="nextStep(2)" class="bg-green-500 text-white px-10 py-2 rounded hover:bg-green-600">Next</button>
                </div>
            </div>

            <!-- STEP 2 -->
            <div id="step2" class="step" style="display:none;">
                <h2 class="font-semibold text-lg mb-3">Required Documents</h2>
                <div class="border border-gray-300 rounded-lg p-6 mb-6 flex flex-col items-center justify-center">
                    <label class="font-medium mb-2">Upload Proof of Employment (e.g., Employment Certificate, Appointment Letter)</label>
                    <img id="previewEmployment" class="hidden w-48 h-48 object-cover mb-3 border rounded" />
                    <input type="file" name="proofOfEmployment" class="border p-2 rounded w-full" id="proofOfEmployment" accept="image/*,application/pdf" onchange="previewImage(event, 'previewEmployment')" required>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(1)" class="bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500">Back</button>
                    <button type="button" onclick="nextStep(3)" class="bg-green-500 text-white px-10 py-2 rounded hover:bg-green-600">Next</button>
                </div>
            </div>

            <!-- STEP 3 -->
            <div id="step3" class="step" style="display:none;">
                <h3 class="font-semibold mb-3">Fill in Fields</h3>
                <div class="space-y-3">
                    <input type="text" name="fullName" placeholder="Full Name" class="border rounded w-full p-2" id="fullName" required>
                    <input type="email" name="email" placeholder="Email" class="border rounded w-full p-2" id="email" required>
                    <input type="text" name="agency" placeholder="Government Agency/Department" class="border rounded w-full p-2" id="agency" required>
                    <textarea name="notes" placeholder="Add Notes (Optional)" class="border rounded w-full p-2 resize-none"></textarea>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(2)" class="bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500">Back</button>
                    <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">Submit for Verification</button>
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-auto pt-6 text-center">
                By submitting, you consent to the use of your documents for verification purposes only.
            </p>
        </form>
    </div>
<?php endif; ?>
</div>

<script>
function openNav() { document.getElementById("sidebar").style.width = "280px"; }
function closeNav() { document.getElementById("sidebar").style.width = "0"; }

let currentStep = 1;
function showStep(step) {
    document.querySelectorAll(".step").forEach(el => el.style.display = "none");
    document.getElementById("step" + step).style.display = "block";
    document.getElementById("currentStep").textContent = step;
    for (let i = 1; i <= 3; i++) {
        const indicator = document.getElementById(`step${i}Indicator`);
        if (i === step) {
            indicator.classList.remove("bg-gray-200", "text-gray-600");
            indicator.classList.add("bg-green-200", "text-green-800", "font-semibold");
        } else {
            indicator.classList.remove("bg-green-200", "text-green-800", "font-semibold");
            indicator.classList.add("bg-gray-200", "text-gray-600");
        }
    }
}
function nextStep(step) { currentStep = step; showStep(step); }
function prevStep(step) { currentStep = step; showStep(step); }

function previewImage(event, previewId) {
    const file = event.target.files[0];
    const preview = document.getElementById(previewId);
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
    }
}
showStep(1);
</script>
</body>
</html>
