<?php
require_once('../utils/utility.php');
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart' => []];

if (!empty($_POST)) {
    $action = getPost('action');
    $id = getPost('id');
    $num = getPost('num');

    // Kiểm tra dữ liệu đầu vào
    if (!in_array($action, ['add', 'update', 'delete'])) {
        $response['message'] = 'Hành động không hợp lệ!';
        echo json_encode($response);
        exit;
    }
    if (!is_numeric($id) || intval($id) <= 0) {
        $response['message'] = 'ID sản phẩm không hợp lệ!';
        echo json_encode($response);
        exit;
    }
    if ($action !== 'delete' && (!is_numeric($num) || intval($num) <= 0)) {
        $response['message'] = 'Số lượng không hợp lệ!';
        echo json_encode($response);
        exit;
    }
    $id = intval($id);
    $num = intval($num);

    $cart = [];
    if (isset($_COOKIE['cart'])) {
        $json = $_COOKIE['cart'];
        $cart = json_decode($json, true);
    }

    switch ($action) {
        case 'add':
            $isFind = false;
            for ($i = 0; $i < count($cart); $i++) {
                if ($cart[$i]['id'] == $id) {
                    $cart[$i]['num'] += $num;
                    $isFind = true;
                    break;
                }
            }
            if (!$isFind) {
                $cart[] = [
                    'id' => $id,
                    'num' => $num
                ];
            }
            setcookie('cart', json_encode($cart), time() + 30 * 24 * 60 * 60, '/');
            $response['success'] = true;
            $response['message'] = 'Đã thêm sản phẩm vào giỏ hàng!';
            break;
        case 'update':
            $updated = false;
            for ($i = 0; $i < count($cart); $i++) {
                if ($cart[$i]['id'] == $id) {
                    $cart[$i]['num'] = $num;
                    $updated = true;
                    break;
                }
            }
            setcookie('cart', json_encode($cart), time() + 30 * 24 * 60 * 60, '/');
            $response['success'] = $updated;
            $response['message'] = $updated ? 'Cập nhật số lượng thành công!' : 'Không tìm thấy sản phẩm trong giỏ!';
            break;
        case 'delete':
            $deleted = false;
            for ($i = 0; $i < count($cart); $i++) {
                if ($cart[$i]['id'] == $id) {
                    array_splice($cart, $i, 1);
                    $deleted = true;
                    break;
                }
            }
            setcookie('cart', json_encode($cart), time() + 30 * 24 * 60 * 60, '/');
            $response['success'] = $deleted;
            $response['message'] = $deleted ? 'Đã xóa sản phẩm khỏi giỏ!' : 'Không tìm thấy sản phẩm trong giỏ!';
            break;
    }
    $response['cart'] = $cart;
    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Không có dữ liệu gửi lên!';
    echo json_encode($response);
    exit;
}