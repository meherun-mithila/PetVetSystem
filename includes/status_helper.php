<?php
/**
 * Status Helper Functions
 * Provides consistent status display across the application
 */

/**
 * Get CSS classes for status display
 * @param string $status The status to get classes for
 * @param string $type The type of status (adoption, appointment, inquiry, etc.)
 * @return array Array with 'bg' and 'text' classes
 */
function getStatusClasses($status, $type = 'general') {
    $status = trim($status ?? '');
    
    switch($type) {
        case 'adoption':
            switch($status) {
                case 'Pending':
                    return ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'];
                case 'Approved':
                    return ['bg' => 'bg-green-100', 'text' => 'text-green-800'];
                case 'Rejected':
                    return ['bg' => 'bg-red-100', 'text' => 'text-red-800'];
                default:
                    return ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
            }
            break;
            
        case 'appointment':
            switch($status) {
                case 'Scheduled':
                    return ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'];
                case 'Completed':
                    return ['bg' => 'bg-green-100', 'text' => 'text-green-800'];
                case 'Cancelled':
                    return ['bg' => 'bg-red-100', 'text' => 'text-red-800'];
                case 'Pending':
                    return ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'];
                case 'In Progress':
                    return ['bg' => 'bg-orange-100', 'text' => 'text-orange-800'];
                default:
                    return ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
            }
            break;
            
        case 'inquiry':
            switch($status) {
                case 'Pending':
                    return ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'];
                case 'Replied':
                    return ['bg' => 'bg-green-100', 'text' => 'text-green-800'];
                case 'Closed':
                    return ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                case 'In Progress':
                    return ['bg' => 'bg-orange-100', 'text' => 'text-orange-800'];
                default:
                    return ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
            }
            break;
            
        default:
            // General status handling
            switch($status) {
                case 'Pending':
                    return ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'];
                case 'Approved':
                case 'Completed':
                case 'Replied':
                    return ['bg' => 'bg-green-100', 'text' => 'text-green-800'];
                case 'Rejected':
                case 'Cancelled':
                    return ['bg' => 'bg-red-100', 'text' => 'text-red-800'];
                case 'Scheduled':
                    return ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'];
                case 'In Progress':
                    return ['bg' => 'bg-orange-100', 'text' => 'text-orange-800'];
                case 'Closed':
                    return ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                default:
                    return ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
            }
    }
}

/**
 * Display a status badge with proper styling
 * @param string $status The status to display
 * @param string $type The type of status
 * @param string $additional_classes Additional CSS classes
 * @return string HTML for the status badge
 */
function displayStatusBadge($status, $type = 'general', $additional_classes = '') {
    $classes = getStatusClasses($status, $type);
    $status_text = htmlspecialchars($status ?? 'Unknown');
    $class_string = $classes['bg'] . ' ' . $classes['text'] . ' ' . $additional_classes;
    
    return '<span class="px-2 py-1 rounded-full text-xs font-medium ' . $class_string . '">' . $status_text . '</span>';
}

/**
 * Get JavaScript function for status classes
 * @return string JavaScript function code
 */
function getStatusClassJS() {
    return "
    function getStatusClass(status) {
        switch(status) {
            case 'Pending': return 'bg-yellow-100 text-yellow-800';
            case 'Approved':
            case 'Completed':
            case 'Replied': return 'bg-green-100 text-green-800';
            case 'Rejected':
            case 'Cancelled': return 'bg-red-100 text-red-800';
            case 'Scheduled': return 'bg-blue-100 text-blue-800';
            case 'In Progress': return 'bg-orange-100 text-orange-800';
            case 'Closed': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }";
}
?>
