<?php
function refreshMenuItems($conn) {
    $menuItems = [];
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.archived = 0 AND c.archived = 0 
              ORDER BY 
                CASE c.name 
                    WHEN 'coffee' THEN 1
                    WHEN 'breakfast' THEN 2
                    WHEN 'addons' THEN 3
                    ELSE 4 
                END,
                p.name";

    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        $menuItem = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'category' => strtolower($row['category_name'])
        ];

        // Handle pricing based on category and available prices
        if ($row['price_medium'] !== null && $row['price_large'] !== null) {
            // Items with size-based pricing (like coffee)
            $menuItem['price'] = [
                'medium' => (float)$row['price_medium'],
                'large' => (float)$row['price_large']
            ];
        } else {
            // Regular items with single price
            $menuItem['price'] = (float)($row['price'] ?? 0);
        }

        $menuItems[] = $menuItem;
    }

    return $menuItems;
}

function clearMenuCache() {
    if (isset($_SESSION['menu_items'])) {
        unset($_SESSION['menu_items']);
    }
}
?>