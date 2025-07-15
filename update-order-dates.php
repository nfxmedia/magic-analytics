<?php

$host = '127.0.0.1';
$db = 'shopware';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all orders created today
    $stmt = $pdo->query("SELECT id, auto_increment FROM `order` WHERE DATE(created_at) = CURDATE() ORDER BY auto_increment ASC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalOrders = count($orders);
    echo "Found $totalOrders orders to update\n";
    
    if ($totalOrders == 0) {
        exit("No orders found\n");
    }
    
    // Calculate date range
    $startDate = new DateTime('2024-01-01');
    $endDate = new DateTime('2025-07-15');
    $totalDays = $startDate->diff($endDate)->days + 1;
    $ordersPerDay = ceil($totalOrders / $totalDays);
    
    echo "Distributing $totalOrders orders across $totalDays days (~$ordersPerDay per day)\n";
    
    $currentDate = clone $startDate;
    $orderIndex = 0;
    
    while ($currentDate <= $endDate && $orderIndex < $totalOrders) {
        // Randomize orders per day between 3 and 8
        $ordersToday = min(rand(3, 8), $totalOrders - $orderIndex);
        
        for ($i = 0; $i < $ordersToday && $orderIndex < $totalOrders; $i++) {
            $order = $orders[$orderIndex];
            $hour = rand(8, 22);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            
            $newDate = $currentDate->format('Y-m-d') . " $hour:$minute:$second";
            
            // Update order date
            $updateStmt = $pdo->prepare("UPDATE `order` SET order_date_time = :date, created_at = :date WHERE id = :id");
            $updateStmt->execute([
                'date' => $newDate,
                'id' => hex2bin($order['id'])
            ]);
            
            $orderIndex++;
        }
        
        if ($orderIndex % 100 == 0) {
            echo "Updated $orderIndex orders...\n";
        }
        
        $currentDate->modify('+1 day');
    }
    
    echo "Successfully updated $orderIndex orders\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}