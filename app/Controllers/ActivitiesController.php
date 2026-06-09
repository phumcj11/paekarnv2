<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityProduct;
use App\Models\ActivityLeadClick;
use App\Models\Property;
use App\Models\VisitorPlace;

class ActivitiesController extends Controller
{
    public function index(): void
    {
        $perPage = 12;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $category = trim((string)($_GET['category'] ?? '')) ?: null;
        $district = trim((string)($_GET['district'] ?? '')) ?: null;
        $zone = trim((string)($_GET['zone'] ?? '')) ?: null;

        $total = ActivityProduct::publishedCount($category, $district, $zone);
        $rows = ActivityProduct::publishedPage($category, $district, $zone, $perPage, $offset);
        $query = array_filter([
            'category' => $category,
            'district' => $district,
            'zone' => $zone,
        ], static fn ($v) => $v !== null && $v !== '');

        $this->view('activities/index', [
            'meta_title'       => 'กิจกรรมท่องเที่ยวกาญจนบุรี ราคาพิเศษ | แพกาญ.com',
            'meta_description' => 'จองกิจกรรม รถเช่า รถนำเที่ยว และทัวร์ในกาญจนบุรี แยกตามอำเภอและโซนท่องเที่ยว',
            'meta_canonical'   => url('/activities' . ($query ? '?' . http_build_query($query) : '')),
            'rows'             => $rows,
            'categories'       => ActivityProduct::CATEGORIES,
            'districtChoices'  => VisitorPlace::DISTRICTS,
            'zoneChoices'      => Property::zonesForSelect(),
            'filterCategory'   => $category,
            'filterDistrict'   => $district,
            'filterZone'       => $zone,
            'filterQuery'      => $query,
            'page'             => $page,
            'totalPages'       => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function show(string $slug): void
    {
        $product = ActivityProduct::findPublishedBySlug($slug);
        if (!$product) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        $options = ActivityProduct::options((int)$product['id']);

        $metaTitle = trim((string)($product['meta_title'] ?? ''));
        $metaDesc = trim((string)($product['meta_description'] ?? ''));

        $this->view('activities/show', [
            'meta_title'       => $metaTitle !== '' ? $metaTitle : $product['title'] . ' — กิจกรรมกาญจนบุรี | แพกาญ.com',
            'meta_description' => $metaDesc !== '' ? $metaDesc : (string)($product['excerpt'] ?? ''),
            'meta_canonical'   => url('/activities/' . $product['slug']),
            'meta_og_image'    => ActivityProduct::coverImageUrl($product),
            'product'          => $product,
            'options'          => $options,
            'categories'       => ActivityProduct::CATEGORIES,
        ]);
    }

    public function leadClick(string $id): void
    {
        $productId = (int)$id;
        $type = trim((string)($_GET['type'] ?? ''));
        if ($productId <= 0 || !in_array($type, ['line', 'phone'], true)) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $product = ActivityProduct::findPublishedById($productId);
        if (!$product) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        ActivityLeadClick::record($productId, (int)($product['provider_id'] ?? 0) ?: null, $type);

        if ($type === 'line') {
            $url = ActivityProduct::lineUrl($product['provider_line_id'] ?? $product['line_id'] ?? '');
            if ($url === '') {
                http_response_code(404);
                $this->view('errors/404');
                return;
            }
            redirect($url);
        }

        $phone = trim((string)($product['provider_phone'] ?? $product['phone'] ?? ''));
        if ($phone === '') {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        redirect('tel:' . preg_replace('/\s+/', '', $phone));
    }
}
