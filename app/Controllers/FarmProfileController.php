<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\FavoriteFarmer;
use App\Models\SellerProfile;
use App\Services\NotificationService;
use App\Services\CustomerStatisticsService;
use App\Services\SellerProfileService;
use App\Services\CloudinaryService;
use App\Services\SensitiveDataCipher;
use App\Services\AuthGuard;
use App\Services\ImageUploadService;
use App\Services\SlugService;
use App\Services\SchemaInspector;

class FarmProfileController extends Controller
{
    public function update()
    {
        csrf_verify();

        $user =
            $this->requireFarmer();

        $this->ensureFarmProfileSchema();

        $seller =
            SellerProfileService::activeShop($user['id']);

        if (!$seller) {
            Session::flash(
                'error',
                'Farm profile not found.'
            );

            return $this->redirect('/ani-mart/public/dashboard');
        }

        $shopName =
            trim($_POST['shop_name'] ?? '');

        if ($shopName === '') {
            Session::flash(
                'error',
                'Farm name is required.'
            );

            return $this->redirect($this->profileReturnUrl());
        }

        $db =
            Database::connection();

        $banner =
            $this->uploadSingleImage('farm_banner', 'farm-banners', $seller->farm_banner ?? null);

        $logo =
            $this->uploadSingleImage('farm_logo', 'farm-logos', $seller->farm_logo ?? null);

        $slug = SlugService::uniqueSeller(
            trim($_POST['slug'] ?? '') ?: $shopName,
            (int) $seller->id,
            'farm-' . (int) $seller->id
        );

        $deliveryFeeInput = trim((string) ($_POST['delivery_fee'] ?? ''));
        $deliveryFee = $deliveryFeeInput === ''
            ? null
            : min(10000, max(0, (float) $deliveryFeeInput));

        $stmt =
            $db->prepare("
                UPDATE seller_profiles
                SET
                    shop_name = ?,
                    slug = ?,
                    description = ?,
                    farm_banner = ?,
                    farm_logo = ?,
                    farm_location = ?,
                    years_experience = ?,
                    response_rate = ?,
                    average_delivery_time = ?,
                    delivery_fee = ?,
                    updated_at = NOW()
                WHERE id = ?
                    AND user_id = ?
            ");

        $farmLocation = trim($_POST['farm_location'] ?? '');
        $stmt->execute([
            $shopName,
            $slug,
            trim($_POST['description'] ?? ''),
            $banner,
            $logo,
            $farmLocation,
            max(0, (int) ($_POST['years_experience'] ?? 0)),
            min(100, max(0, (int) ($_POST['response_rate'] ?? $seller->response_rate ?? 0))),
            trim($_POST['average_delivery_time'] ?? ''),
            $deliveryFee,
            $seller->id,
            $user['id']
        ]);

        $imageService = new CloudinaryService();
        foreach ([[$banner, $seller->farm_banner ?? null], [$logo, $seller->farm_logo ?? null]] as [$newUrl, $oldUrl]) {
            if ($newUrl !== $oldUrl) {
                if (!empty($oldUrl)) {
                    $imageService->deleteByUrl((string) $oldUrl, false);
                }
                $asset = $newUrl ? $imageService->findByUrl((string) $newUrl) : null;
                if ($asset) {
                    $imageService->associate((string) $asset->public_id, 'seller_profile', (int) $seller->id, (int) $user['id']);
                }
            }
        }

        $cipher = new SensitiveDataCipher();
        $db->prepare('UPDATE seller_profiles SET farm_location_encrypted = ? WHERE id = ? AND user_id = ?')
            ->execute([
                $cipher->encrypt($farmLocation, 'seller_profiles.farm_location'),
                $seller->id,
                $user['id'],
            ]);

        $this->uploadGallery($seller->id, 'farm_gallery', 'farm');
        $this->uploadGallery($seller->id, 'harvest_gallery', 'harvest');

        Session::flash(
            'success',
            'Farm profile updated successfully.'
        );

        return $this->redirect($this->profileReturnUrl());
    }

    public function deleteImage()
    {
        csrf_verify();

        $user =
            $this->requireFarmer();

        $this->ensureFarmProfileSchema();

        $imageId =
            (int) ($_POST['image_id'] ?? 0);

        $db =
            Database::connection();

        $imageStmt = $db->prepare("SELECT fpi.image_path FROM farm_profile_images fpi INNER JOIN seller_profiles sp ON sp.id = fpi.seller_profile_id WHERE fpi.id = ? AND sp.user_id = ? LIMIT 1");
        $imageStmt->execute([$imageId, $user['id']]);
        $imageUrl = (string) ($imageStmt->fetchColumn() ?: '');

        $stmt =
            $db->prepare("
                DELETE farm_profile_images
                FROM farm_profile_images
                INNER JOIN seller_profiles
                    ON seller_profiles.id = farm_profile_images.seller_profile_id
                WHERE farm_profile_images.id = ?
                    AND seller_profiles.user_id = ?
            ");

        $stmt->execute([
            $imageId,
            $user['id']
        ]);

        if ($stmt->rowCount() > 0 && $imageUrl !== '') {
            (new CloudinaryService())->deleteByUrl($imageUrl, false);
        }

        Session::flash(
            'success',
            'Gallery image removed.'
        );

        return $this->redirect($this->profileReturnUrl());
    }

    public function show($slug)
    {
        $this->ensureFarmProfileSchema();

        $seller =
            SellerProfile::findPublicBySlug($slug);

        if (!$seller) {
            http_response_code(404);
            echo '404 - Farm Not Found';
            return;
        }

        $metrics =
            SellerProfile::metrics($seller->id);

        $seller->customer_rating =
            $metrics['customer_rating'];

        $user = Session::get('user');
        $isFollowing = false;

        if (
            $user &&
            strtolower($user['role'] ?? '') === 'customer'
        ) {
            $isFollowing = FavoriteFarmer::isFollowing(
                $user['id'],
                $seller->id
            );
        }

        $postModel = new CommunityPost();
        $communityPosts = $postModel->fetchPublicByAuthor(
            (int) $seller->user_id,
            $user['id'] ?? null,
            12
        );
        $commentModel = new CommunityComment();
        foreach ($communityPosts as $post) {
            $post->images = $postModel->images($post->id);
            $post->comments = $commentModel->commentsByPost($post->id);
        }

        $activeShop = $user && strtolower($user['role'] ?? '') === 'farmer'
            ? SellerProfileService::activeShop($user['id'])
            : null;

        return $this->view(
            'farm/show',
            [
                'seller' => $seller,
                'theme' => \App\Services\SellerProfileService::themes()[$seller->shop_theme ?? 'fresh-farm'] ?? \App\Services\SellerProfileService::themes()['fresh-farm'],
                'farmGallery' => SellerProfile::gallery($seller->id, 'farm'),
                'harvestGallery' => SellerProfile::gallery($seller->id, 'harvest'),
                'products' => SellerProfile::products($seller->id),
                'featuredProducts' => \App\Services\SellerProfileService::featuredProducts($seller->id),
                'metrics' => $metrics,
                'customerStats' => CustomerStatisticsService::forSeller((int) $seller->id),
                'isFollowing' => $isFollowing,
                'followersCount' => FavoriteFarmer::followersCount($seller->id),
                'communityPosts' => $communityPosts,
                'activeShop' => $activeShop
            ]
        );
    }

    public function follow($slug)
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'customer') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Please login as a customer to follow farms.',
                'redirect' => '/ani-mart/public/login'
            ]);
            exit;
        }

        $seller = SellerProfile::findPublicBySlug($slug);

        if (!$seller) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Farm not found.'
            ]);
            exit;
        }

        $followed = FavoriteFarmer::follow(
            $user['id'],
            $seller->id
        );

        if ($followed) {
            NotificationService::send(
                $seller->user_id,
                'New farm follower',
                trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) . ' started following your farm.',
                'promotion',
                'bi-person-plus',
                'success',
                '/ani-mart/public/farm/' . urlencode($seller->slug),
                'farm',
                $seller->id,
                '/ani-mart/public/farm/' . urlencode($seller->slug)
            );
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_following' => true,
            'followers_count' => FavoriteFarmer::followersCount($seller->id),
            'message' => $followed ? 'You are now following this farm.' : 'You are already following this farm.'
        ]);
        exit;
    }

    public function unfollow($slug)
    {
        csrf_verify();
        
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'customer') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Please login as a customer to unfollow farms.',
                'redirect' => '/ani-mart/public/login'
            ]);
            exit;
        }

        $seller = SellerProfile::findPublicBySlug($slug);

        if (!$seller) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Farm not found.'
            ]);
            exit;
        }

        FavoriteFarmer::unfollow(
            $user['id'],
            $seller->id
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_following' => false,
            'followers_count' => FavoriteFarmer::followersCount($seller->id),
            'message' => 'You have unfollowed this farm.'
        ]);
        exit;
    }

    private function requireFarmer()
    {
        return AuthGuard::farmer();
    }

    private function profileReturnUrl(): string
    {
        return '/ani-mart/public/seller-center/shop/customize';
    }

    private function uploadSingleImage($field, $folder, $currentValue)
    {
        if (
            empty($_FILES[$field])
            || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return $currentValue;
        }

        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            Session::flash(
                'error',
                $this->uploadErrorMessage($_FILES[$field]['error'])
            );

            $this->redirect($this->profileReturnUrl());
        }

        return $this->storeUploadedImage(
            [
                'name' => $_FILES[$field]['name'] ?? '',
                'tmp_name' => $_FILES[$field]['tmp_name'] ?? '',
                'size' => $_FILES[$field]['size'] ?? 0,
                'type' => $_FILES[$field]['type'] ?? '',
                'error' => $_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE,
            ],
            $folder
        );
    }

    private function uploadGallery($sellerProfileId, $field, $type)
    {
        if (
            empty($_FILES[$field])
            || empty($_FILES[$field]['name'][0])
        ) {
            return;
        }

        $db =
            Database::connection();

        foreach ($_FILES[$field]['name'] as $index => $name) {
            if ($_FILES[$field]['error'][$index] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($_FILES[$field]['error'][$index] !== UPLOAD_ERR_OK) {
                Session::flash(
                    'error',
                    $this->uploadErrorMessage($_FILES[$field]['error'][$index])
                );

                $this->redirect($this->profileReturnUrl());
            }

            $path =
                $this->storeUploadedImage(
                    [
                        'name' => $name,
                        'tmp_name' => $_FILES[$field]['tmp_name'][$index] ?? '',
                        'size' => $_FILES[$field]['size'][$index] ?? 0,
                        'type' => $_FILES[$field]['type'][$index] ?? '',
                        'error' => $_FILES[$field]['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    ],
                    'farm-galleries'
                );

            $stmt =
                $db->prepare("
                    INSERT INTO farm_profile_images
                    (
                        seller_profile_id,
                        image_type,
                        image_path,
                        sort_order,
                        created_at
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, NOW()
                    )
                ");

            $stmt->execute([
                $sellerProfileId,
                $type,
                $path,
                $index
            ]);

            $imageService = new CloudinaryService();
            $asset = $imageService->findByUrl((string) $path);
            if ($asset) {
                $imageService->associate((string) $asset->public_id, 'seller_profile', (int) $sellerProfileId, (int) (Session::get('user')['id'] ?? 0));
            }
        }
    }

    private function storeUploadedImage(array $file, $folder)
    {
        $upload = (new ImageUploadService())->publicFile($file, (string) $folder);
        if (!$upload || empty($upload['secure_url'])) {
            Session::flash(
                'error',
                'We could not upload one of your images.'
            );

            $this->redirect($this->profileReturnUrl());
        }

        return $upload['secure_url'];
    }

    private function uploadErrorMessage($errorCode)
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The image is larger than the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The image is larger than the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload temp folder is missing.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the image upload.'
        ];

        return $messages[$errorCode]
            ?? 'One of your images could not be uploaded.';
    }

    private function ensureFarmProfileSchema()
    {
        SchemaInspector::requireColumns('seller_profiles', [
            'slug', 'farm_banner', 'farm_logo', 'farm_location', 'farming_method',
            'years_experience', 'response_rate', 'average_delivery_time', 'delivery_fee', 'updated_at',
        ]);
        SchemaInspector::requireColumns('farm_profile_images', [
            'seller_profile_id', 'image_type', 'image_path', 'caption', 'sort_order', 'created_at',
        ]);

        $db = Database::connection();

        $profiles =
            $db->query("
                SELECT id, shop_name
                FROM seller_profiles
                WHERE slug IS NULL
                    OR slug = ''
            ")->fetchAll();

        foreach ($profiles as $profile) {
            $slug =
                SlugService::uniqueSeller($profile->shop_name ?? ('farm-' . $profile->id), (int) $profile->id, 'farm-' . (int) $profile->id);

            $stmt =
                $db->prepare("
                    UPDATE seller_profiles
                    SET slug = ?
                    WHERE id = ?
                ");

            $stmt->execute([
                $slug,
                $profile->id
            ]);
        }
    }
}
