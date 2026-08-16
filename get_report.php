<?php
// get_report.php

include('db_connection.php');

if (isset($_GET['id'])) {
    $report_id = $_GET['id'];

    $sql = "SELECT problem_type, description, admin_response FROM reports WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
        echo json_encode($report);
    } else {
        echo json_encode(["error" => "Report not found"]);
    }

    $stmt->close();
    $conn->close();
}
