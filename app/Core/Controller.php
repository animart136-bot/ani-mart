<?php

namespace App\Core;

use App\Core\Database;
use App\Core\Session;
use App\Models\Cart;
use App\Services\SellerProfileService;

class Controller
{
    /*
    |--------------------------------------------------------------------------
    | Load View
    |--------------------------------------------------------------------------
    */
    protected function view(
        $view,
        $data = []
    ) {
        $data =
            array_merge(
                $this->sharedData(),
                $data
            );

        extract($data);

        $path =
            __DIR__ . "/../Views/" .
            $view .
            ".php";

        ob_start();

        try {
            require $path;
            $markup = (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        echo normalize_app_markup($markup);
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect Helper
    |--------------------------------------------------------------------------
    */
    protected function redirect($path)
    {
        header('Location: ' . normalize_app_url((string) $path));
        exit;
    }

    protected function nullableDate($date)
    {
        if (
            $date === null ||
            trim((string) $date) === ''
        ) {
            return null;
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    protected function requireActiveShop()
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user || strtolower($user['role'] ?? '') !== 'farmer') {
            header('Location: ' . app_url('login'));
            exit;
        }

        $activeShop = SellerProfileService::activeShop($user['id']);

        if (!$activeShop) {
            Session::flash(
                'error',
                'Please create or select a shop before continuing.'
            );

            $this->redirect(app_url('seller-center/shops'));
        }

        return $activeShop;
    }

    protected function sharedData()
    {
        $data = [];

        $user =
            $_SESSION['user'] ?? null;

        /*
|--------------------------------------------------------------------------
| Cart Count
|--------------------------------------------------------------------------
*/

        $data['cartCount'] = 0;

        if ($user) {
            try {
                $data['cartCount'] =
                    Cart::count(
                        $user['id']
                    );
            } catch (\PDOException $e) {
                $data['cartCount'] = 0;
            }
        }

        if (
            $user &&
            ($user['role'] ?? '') === 'customer'
        ) {

            $db =
                Database::getInstance()
                ->connect();

            $stmt =
                $db->prepare("

                SELECT status

                FROM farmer_applications

                WHERE user_id = ?

                LIMIT 1

            ");

            $stmt->execute([
                $user['id']
            ]);

            $application =
                $stmt->fetch();

            $data['applicationStatus'] =
                $application->status
                ?? null;
        }

        if (
            $user &&
            ($user['role'] ?? '') === 'farmer'
        ) {
            $data['activeShop'] = SellerProfileService::activeShop($user['id']);
            $data['userShops'] = SellerProfileService::userShops($user['id']);
        }

        return $data;
    }
}
