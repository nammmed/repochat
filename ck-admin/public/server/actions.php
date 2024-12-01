<?php
include './config.php';
include './functions.php';
include './actions/logs.php';
include './actions/ISPManager.php';
include './actions/files.php';
include './actions/locations.php';
include './actions/sites.php';
include './actions/user.php';
include './actions/VPS.php';
include './actions/cloudflare.php';

session_start();

try {
    $db = new PDO('mysql:host=localhost;dbname=' . $dbname . ';charset=utf8', $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if (!empty($_POST)) {
        $data = $_POST;
    } elseif ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $_GET;
    }

    if (isset($data['action'])) {
        logAction(isset($_SESSION['user']) ? $_SESSION['user'] : 0, $data['action'], json_encode($data));
    }

    $authNotRequiredActions = ['loginUser', 'checkSession', 'ispLogin', 'cleanupOldTokens'];
    $isAuthenticated = isset($_SESSION['user']) && $_SESSION['user'];
    if (!in_array($data['action'], $authNotRequiredActions) && !$isAuthenticated) {
        throw new Exception('Необходима авторизация');
    }

    switch ($data['action']) {
        case 'checkSession':
            echo json_encode(['user' => $_SESSION['user']]);
            break;
        case 'loginUser':
            loginUser($data);
            break;
        case 'updateSiteAliases':
            updateSiteAliases($data);
            break;
        case 'updateSite':
            updateSite($data);
            break;
        case 'addSite':
            addSite($data);
            break;
        case 'getSites':
            getSites();
            break;
        case 'getSite':
            getSite($data);
            break;
        case 'deleteSite':
            deleteSite($data);
            break;
        case 'getVPS':
            getVPS();
            break;
        case 'addVPS':
            addVPS($data);
            break;
        case 'updateVPSField':
            updateVPSField($data);
            break;
        case 'deleteVPS':
            deleteVPS($data);
            break;
        case 'restoreVPS':
            restoreVPS($data);
            break;
        case 'getFiles':
            getFileList();
            break;
        case 'addFile':
            addFile($data);
            break;
        case 'updateFileField':
            updateFileField($data);
            break;
        case 'deleteFile':
            deleteFile($data);
            break;
        case 'restoreFile':
            restoreFile($data);
            break;
        case 'getUploadLocations':
            getUploadLocations();
            break;
        case 'addUploadLocation':
            addUploadLocation($data);
            break;
        case 'updateUploadLocationField':
            updateUploadLocationField($data);
            break;
        case 'deleteUploadLocation':
            deleteUploadLocation($data);
            break;
        case 'restoreUploadLocation':
            restoreUploadLocation($data);
            break;
        case 'getISPManagerFile':
            getISPManagerFile($data['filepath'], $data['vps'], true);
            break;
        case 'saveISPManagerFile':
            saveISPManagerFile($data['filepath'], $data['vps'], $data['fileData'], true);
            break;
        case 'uploadISPManagerFile':
            uploadSFTPFile();
            break;
        case 'getConfig':
            getConfig($data);
            break;
        case 'saveConfig':
            saveConfig($data);
            break;
        case 'updateSiteConfig':
            updateSiteConfig($data);
            break;
        case 'changePassword':
            changePassword($data);
            break;
        case 'changeSecurityKey':
            changeSecurityKey($data);
            break;
        case 'getISPAliases':
            getISPAliases($data['domain'], $data['VPSId'], true);
            break;
        case 'updateAllAliases':
            updateAllAliases($data['siteId']);
            break;
        case 'loginToISPManager':
            loginToISPManager($data);
            break;
        case 'ispLogin':
            handleISPLogin($data['token']);
            break;
        case 'cleanupOldTokens':
            cleanupOldTokens();
            break;
        case 'loginToWordPress':
            loginToWordPress($data);
            break;
        case 'saveCloudflareCredentials':
            saveCloudflareCredentials($data);
            break;
        case 'addSiteToCloudflare':
            addSiteToCloudflare($data);
            break;
        case 'enableCloudflareAlwaysUseHTTPS':
            enableCloudflareAlwaysUseHTTPS($data);
            break;
        case 'importCloudflareDNSRecords':
            importCloudflareDNSRecords($data);
            break;
        case 'getCloudflareNSServers':
            getCloudflareNSServers($data);
            break;
        case 'createCloudflareFirewallRule':
            createCloudflareFirewallRule($data);
            break;
        case 'toggleCloudflareFirewallRule':
            toggleCloudflareFirewallRule($data);
            break;
        case 'getCloudflareTrafficAnalytics':
            getCloudflareTrafficAnalytics($data);
            break;
        case 'checkCloudflareForDDoS':
            checkCloudflareForDDoS($data);
            break;
        case 'getCloudflareTLS13Status':
            getCloudflareTLS13Status($data);
            break;
        case 'setCloudflareTLS13Status':
            setCloudflareTLS13Status($data);
            break;
        case 'setCloudflareECHStatus':
            setCloudflareECHStatus($data);
            break;
        case 'removeSiteFromCloudflare':
            removeSiteFromCloudflare($data);
            break;
        case 'setCloudflareSSLMode':
            setCloudflareSSLMode($data);
            break;
        case 'getCloudflareZonesList':
            getCloudflareZonesList($data);
            break;
        case 'getDNSRecords':
            getDNSRecords($data);
            break;
        case 'updateDNSRecord':
            updateDNSRecord($data);
            break;
        case 'updateDNSRecords':
            updateDNSRecords($data);
            break;
        default:
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}