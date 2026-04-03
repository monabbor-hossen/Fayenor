<?php
// management/chat.php (Client-side version)
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

$db = (new Database())->getConnection();

// 1. SMART FETCHING – client only sees their own projects
if ($_SESSION['role'] === 'client') {
    $account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT client_id, company_name FROM clients WHERE account_id = ? AND is_active = 1 ORDER BY company_name ASC");
    $stmt->execute([$account_id]);
} else {
    $stmt = $db->prepare("SELECT client_id, company_name FROM clients WHERE is_active = 1 ORDER BY company_name ASC");
    $stmt->execute();
}
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_client = $_GET['client_id'] ?? ($clients[0]['client_id'] ?? 0);

// Security Check
$owns_project = false;
foreach($clients as $c) { if($c['client_id'] == $active_client) $owns_project = true; }
if (!$owns_project && count($clients) > 0) $active_client = $clients[0]['client_id'];

$is_mobile_chat_active = isset($_GET['client_id']) ? true : false;
$active_name = '';
foreach($clients as $c) { if($c['client_id'] == $active_client) $active_name = $c['company_name']; }
?>




<div class="container-fluid py-4 h-100 chat-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0">
                <i class="bi bi-chat-dots text-secondary me-2"></i>Support Messages
            </h3>
            <p class="text-white-50 small mb-0">Secure encrypted conversations</p>
        </div>
    </div>

    <div class="row g-0 chat-container-box" style="height: calc(100vh - 210px); min-height: 500px;">

        <!-- ===== SIDEBAR ===== -->
        <div id="chatSidebarList"
             class="col-md-4 chat-sidebar overflow-auto h-100 <?php echo $is_mobile_chat_active ? 'd-none d-md-flex flex-column' : 'd-flex flex-column'; ?>">

            <div class="chat-sidebar-header">
                <i class="bi bi-people-fill me-2"></i>Active Projects
            </div>

            <div class="list-group list-group-flush bg-transparent flex-grow-1 pb-3">
                <?php if (count($clients) > 0): ?>
                    <?php foreach ($clients as $c):
                        $is_active_class = ($c['client_id'] == $active_client) ? 'active-chat' : '';
                    ?>
                        <a href="#"
                           onclick="switchChat(event, <?php echo $c['client_id']; ?>, '<?php echo htmlspecialchars(addslashes($c['company_name']), ENT_QUOTES); ?>', this)"
                           class="client-chat-link <?php echo $is_active_class; ?>">
                            <div class="chat-avatar me-3">
                                <?php echo strtoupper(substr($c['company_name'], 0, 2)); ?>
                            </div>
                            <div class="text-truncate">
                                <div class="chat-item-name text-truncate"><?php echo htmlspecialchars($c['company_name']); ?></div>
                                <div class="chat-item-sub">Tap to view messages</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-white-50 small">No active projects found.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== MAIN CHAT PANEL ===== -->
        <div id="chatMainBox"
             class="col-md-8 d-flex flex-column h-100 <?php echo $is_mobile_chat_active ? 'd-flex' : 'd-none d-md-flex'; ?>"
             style="background: radial-gradient(ellipse at top, rgba(2,48,32,0.08) 0%, rgba(0,0,0,0.3) 100%);">

            <?php if ($active_client): ?>

                <!-- Chat Header -->
                <div class="chat-header-bar d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="#" onclick="closeMobileChat(event)" class="text-white me-3 d-md-none text-decoration-none">
                            <i class="bi bi-arrow-left fs-5"></i>
                        </a>
                        <div class="chat-avatar me-3" style="width:38px;height:38px;font-size:0.8rem;">
                            <?php echo strtoupper(substr($active_name, 0, 2)); ?>
                        </div>
                        <div>
                            <h6 class="text-white fw-bold mb-0" style="font-size:0.95rem;">
                                <span class="chat-header-dot"></span>
                                <span id="chatHeaderTitle"><?php echo htmlspecialchars($active_name); ?></span>
                            </h6>
                            <small id="chatHeaderSub" class="text-secondary" style="font-size:0.72rem; letter-spacing:0.3px;">
                                Secure · Encrypted Conversation
                            </small>
                        </div>
                    </div>
                    <div>
                        <span class="badge" style="background:rgba(176,196,222,0.1); color:#B0C4DE; border:1px solid rgba(176,196,222,0.2); font-size:0.68rem; letter-spacing:0.5px; padding:6px 10px;">
                            <i class="bi bi-shield-lock me-1"></i>E2E Encrypted
                        </span>
                    </div>
                </div>

                <!-- Messages Area -->
                <div id="chatBox" class="flex-grow-1 p-3 p-md-4 overflow-auto d-flex flex-column" style="scroll-behavior: smooth;">
                    <div class="text-center text-white-50 mt-5">
                        <div class="spinner-border spinner-border-sm me-2" style="color:#B0C4DE;"></div>
                        Loading messages...
                    </div>
                </div>

                <!-- Input Bar -->
                <div class="chat-input-bar mt-auto">
                    <div class="chat-input-wrap">
                        <textarea id="chatInput"
                            class="form-control"
                            placeholder="Type your message..."
                            rows="1"
                            oninput="this.style.height='auto'; this.style.height=this.scrollHeight+'px'"></textarea>
                        <button onclick="sendMessage()" class="btn-chat-send">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <div class="chat-empty-state">
                    <div class="chat-empty-icon">
                        <i class="bi bi-chat-square-dots"></i>
                    </div>
                    <h5 class="text-white fw-bold mt-2">No Conversation Selected</h5>
                    <p class="text-white-50 small">Choose a project from the sidebar to view or send messages.</p>
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<script>
    window.currentChatClientId = <?php echo $active_client ? $active_client : 0; ?>;
</script>

<?php require_once '../portal/includes/footer.php'; ?>