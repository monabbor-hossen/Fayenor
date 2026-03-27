<?php
class ProgressCalc {
    public static function calculateCompletion($milestones) {
        $total = count($milestones);
        $completed = 0;
        foreach ($milestones as $status) {
            if ($status === 'Approved' || $status === true) {
                $completed++;
            }
        }
        return ($completed / $total) * 100;
    }
}
?>