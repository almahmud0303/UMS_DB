<?php
// includes/debug_toggle.php - Debug Toggle Component

// Enable debug mode if requested
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    QueryDebugger::enable();
} else {
    QueryDebugger::disable();
}

// Function to render debug toggle button
function renderDebugToggle() {
    $isEnabled = QueryDebugger::isEnabled();
    
    // Get current URL without query parameters
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    
    // Build toggle URL
    if ($isEnabled) {
        // Remove debug parameter
        $toggleUrl = $currentUrl;
    } else {
        // Add debug parameter
        $toggleUrl = $currentUrl . '?debug=1';
    }
    
    $html = '<div class="debug-toggle">';
    $html .= '<a href="' . htmlspecialchars($toggleUrl) . '" class="btn btn-sm btn-outline-info">';
    $html .= '<i class="fas fa-' . ($isEnabled ? 'eye-slash' : 'eye') . ' me-1"></i>';
    $html .= $isEnabled ? 'Hide Queries' : 'Show Queries';
    $html .= '</a>';
    $html .= '</div>';
    
    return $html;
}

// Function to render debug panel
function renderDebugPanel() {
    return QueryDebugger::renderDebugPanel();
}
?>
