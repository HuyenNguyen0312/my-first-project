<?php
require_once('database/dbhelper.php');

function is_api_request() {
    return (
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    );
}

if (!empty($_POST)) {
    $cart = [];
    if (isset($_COOKIE['cart'])) {
        $json = $_COOKIE['cart'];
        $cart = json_decode($json, true);
    }
    if ($cart == null || count($cart) == 0) {
        if (is_api_request()) {
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
            exit;
        } else {
            header('Location: ../index.php');
            die();
        }
    }
    // Lấy và kiểm tra dữ liệu đầu vào
    $fullname = isset($_POST['fullname']) ? addslashes(trim($_POST['fullname'])) : '';
    $email = isset($_POST['email']) ? addslashes(trim($_POST['email'])) : '';
    $phone_number = isset($_POST['phone_number']) ? addslashes(trim($_POST['phone_number'])) : '';
    $address = isset($_POST['address']) ? addslashes(trim($_POST['address'])) : '';
    $note = isset($_POST['note']) ? addslashes(trim($_POST['note'])) : '';
    $orderDate = date('Y-m-d H:i:s');

    // Thêm đơn hàng
    $sql = "INSERT INTO orders(fullname,email, phone_number,address, note, order_date) 
    VALUES ('$fullname','$email','$phone_number','$address','$note','$orderDate')";
    // Lấy orderId vừa tạo
    $orderId = executeResult("SELECT id FROM orders WHERE order_date = '$orderDate' ORDER BY id DESC LIMIT 1");
    $orderId = $orderId ? $orderId[0]['id'] : 0;

    // Lấy userId nếu có
    $userId = 'NULL';
    if (isset($_COOKIE['tendangnhap'])) {
        $tendangnhap = addslashes($_COOKIE['tendangnhap']);
        $sql = "SELECT * FROM user WHERE tendangnhap = '$tendangnhap'";
        $user = executeResult($sql);
        if ($user && isset($user[0]['id_user'])) {
            $userId = $user[0]['id_user'];
        }
    }

    // Lấy danh sách sản phẩm trong giỏ
    $idList = [];
    foreach ($cart as $item) {
        $idList[] = intval($item['id']);
    }
    if (count($idList) > 0) {
        $idListStr = implode(',', $idList); 
        $sql = "SELECT * FROM product WHERE id IN ($idListStr)";
        $cartList = executeResult($sql);
    } else {
        $cartList = [];
    }
    $status = 'Đang chuẩn bị';
    // Thêm chi tiết đơn hàng
    foreach ($cartList as $item) {
        $num = 0;
        foreach ($cart as $value) {
            if ($value['id'] == $item['id']) {
                $num = intval($value['num']);
                break;
            }
        }
        $price = intval($item['price']);
        $sql = "INSERT INTO order_details(order_id, product_id, id_user, num, price, status) VALUES ('$orderId', '{$item['id']}', '$userId', '$num', '$price', '$status')";
        execute($sql);
    }
    setcookie('cart', '[]', time() - 1000, '/');
    if (is_api_request()) {
        echo json_encode(['success' => true, 'message' => 'Đặt hàng thành công!']);
        exit;
    } else {
        echo '<script language="javascript">\nalert("Thêm đơn hàng thành công!");\nwindow.location = "../history_product.php";\n</script>';
    }
}
