<?php
// reports/contract.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Tenant ID missing!");
}

// Fetch Tenant Info
$stmt = $pdo->prepare("SELECT t.*, h.house_number, h.rent_amount 
                       FROM tenants t 
                       LEFT JOIN houses h ON t.house_id = h.id 
                       WHERE t.id = ?");
$stmt->execute([$id]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die("Tenant not found!");
}

// Landlord Details (Fixed as per request)
$landlord = [
    'name' => 'EZEKIEL MWAKASEGE',
    'house_no' => '03',
    'mtaa' => 'CHANGANYIKENI',
    'phone' => '0755348623'
];

// Calculation for cycle (3 months)
$rent_per_month = $tenant['rent_amount'] ?: 0;
$rent_3_months = $rent_per_month * 3;
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mkataba wa Upangaji - <?php echo $tenant['full_name']; ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.6; padding: 40px; color: #333; max-width: 800px; margin: auto; background: #f9f9f9; }
        .contract-container { background: #fff; padding: 40px; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; text-decoration: underline; font-weight: bold; margin-bottom: 30px; font-size: 1.4rem; }
        .section-title { font-weight: bold; margin-top: 20px; text-decoration: underline; }
        .content { margin-bottom: 20px; }
        .field-line { border-bottom: 1px dotted #000; display: inline-block; min-width: 200px; padding: 0 5px; font-weight: bold; }
        .footer-signature { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { width: 45%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; }
        .no-print { text-align: center; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
            body { background: #fff; padding: 0; }
            .contract-container { border: none; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 5px;">Chapisha Mkataba (Print/PDF)</button>
    <a href="../admin/tenants.php" style="margin-left: 10px; color: #007bff;">Rudi Kwenye Orodha</a>
</div>

<div class="contract-container">
    <div class="header">MAKUBALIANO YA UPANGAJI CHUMBA/NYUMBA</div>

    <div class="content">
        1. Jina la mwenye nyumba au mwakilishi wake: <span class="field-line"><?php echo $landlord['name']; ?></span><br>
        2. Namba ya nyumba: <span class="field-line"><?php echo $landlord['house_no']; ?></span><br>
        3. Mtaa au kitongoji nyumba ilipo: <span class="field-line"><?php echo $landlord['mtaa']; ?></span><br>
        4. Namba ya simu: <span class="field-line"><?php echo $landlord['phone']; ?></span>
    </div>

    <div class="section-title">TAARIFA BINAFSI ZA MPANGAJI</div>
    <div class="content">
        1. Jina kamili la mpangaji: <span class="field-line"><?php echo $tenant['full_name']; ?></span><br>
        2. Jina la anuani ya Ndugu/mwajili wake: <span class="field-line"></span><br>
        3. Namba ya simu: <span class="field-line"><?php echo $tenant['phone']; ?></span>
    </div>

    <div class="section-title">MASHARTI YA UPANGAJI</div>
    <div class="content">
        1. Mtu mlevi haluhusiwi kuwa mpangaji kwahiyo ni marufuku kunywa au kuingiza vilevi vya aina yoyote ikiwemo madawa ya kulevya.<br>
        2. Wapangaji wanatakiwa kujilipia wenyewe Maji, umeme, uondoaji wa majitaka kwa utaratibu watakao kubaliana wenyewe.<br>
        3. Mpangaji anatakiwa kulipia mda atakao kaa kwenye nyumba/chumba ili kumpunguzia makali mpangaji ataruhusiwa kulipa mda atakao kaa kwa awamu ya miezi mitatu badala ya mwaka mmoja.<br>
        4. Kwahiyo Ndugu <span class="field-line"><?php echo $tenant['full_name']; ?></span> Amelipia Tshs <span class="field-line"><?php echo number_format($rent_3_months); ?></span> Kwaajili ya miezi <span class="field-line">3 (Tatu)</span> kuanzia tarehe <span class="field-line"><?php echo date('d/m/Y', strtotime($tenant['start_date'])); ?></span> hadi <span class="field-line"><?php echo date('d/m/Y', strtotime($tenant['end_date'])); ?></span><br>
        5. Iwapo mpangaji ataamua kuendeleza au kukuendelea na upangaji baada ya kipindi hiki kumalizika itabidi atoe taarifa ya siku thelathini (30) kabla kwa mwenye nyumba kabla ya kumalizika kwa muda wa makubaliano hayo.<br>
        6. Kadhalika Iwapo mwenye nyumba ataamua kutoendelea na upangaji itabidi atoe taarifa ya siku tisini (90).<br>
        7. Iwapo nitaamua kuhama baada ya muda kumalizika itabidi niache nyumba katika hali nzuri kama nilivyoikuta wakati nilipoingia ndani (i.e., rangi, soketi za umeme na vitasa katika hali nzuri).<br>
        8. Nathibitisha kwamba nimesoma mkataba huu nakuhahidi kutekeleza vyote na endepo nitashindwa Naomba upangaji wangu usitishwe.
    </div>

    <div class="footer-signature">
        <div class="signature-box">
            JINA LA MPANGAJI
            <div class="sig-line"><?php echo $tenant['full_name']; ?></div>
            SAHIHI: ..............................
        </div>
        <div class="signature-box">
            JINA LA MWENYE NYUMBA
            <div class="sig-line"><?php echo $landlord['name']; ?></div>
            SAHIHI: ..............................
        </div>
    </div>
</div>

</body>
</html>
