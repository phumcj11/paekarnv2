<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\CompareService;

class CompareController extends Controller
{
    public function index(): void
    {
        $items = CompareService::parseQueryItems((string)($_GET['u'] ?? ''));
        if ($items === [] && Auth::isCustomer()) {
            $cid = Auth::customerId();
            $items = $cid ? CompareService::customerItems((int)$cid) : [];
        }

        $rows = CompareService::loadCompareRows($items);

        $this->view('compare/index', [
            'meta_title' => 'เทียบแพที่เลือก — แพกาญ.com',
            'meta_description' => 'เทียบแพพักและบ้านพูลวิลล่าแต่ละหลัง ดูราคา ความจุ ห้องนอน รีวิว และสิ่งอำนวยความสะดวกในหน้าเดียว',
            'meta_canonical' => url('/compare'),
            'rows' => $rows,
            'encoded_items' => CompareService::encodeItems($items),
            'max_items' => CompareService::MAX_ITEMS,
        ]);
    }

    public function items(): void
    {
        $items = $this->payloadItems();
        if ($items === [] && Auth::isCustomer()) {
            $cid = Auth::customerId();
            $items = $cid ? CompareService::customerItems((int)$cid) : [];
        }

        $rows = CompareService::loadCompareRows($items);
        $this->json([
            'ok' => true,
            'items' => CompareService::normalizeItems($items),
            'rows' => $rows,
            'expired' => max(0, count(CompareService::normalizeItems($items)) - count($rows)),
            'max' => CompareService::MAX_ITEMS,
            'db_ready' => CompareService::tableReady(),
        ]);
    }

    public function sync(): void
    {
        $items = $this->payloadItems();
        if (!Auth::isCustomer()) {
            $rows = CompareService::loadCompareRows($items);
            $this->json([
                'ok' => true,
                'items' => CompareService::normalizeItems($items),
                'rows' => $rows,
                'expired' => max(0, count(CompareService::normalizeItems($items)) - count($rows)),
                'max' => CompareService::MAX_ITEMS,
                'db_ready' => CompareService::tableReady(),
            ]);
        }

        $cid = Auth::customerId();
        $result = CompareService::syncCustomer((int)$cid, $items);
        $result['ok'] = true;
        $result['max'] = CompareService::MAX_ITEMS;
        $this->json($result);
    }

    public function clear(): void
    {
        if (Auth::isCustomer() && CompareService::tableReady()) {
            $cid = Auth::customerId();
            if ($cid) {
                \App\Core\Database::delete('compare_items', 'customer_id = :c', ['c' => (int)$cid]);
            }
        }

        $this->json(['ok' => true, 'items' => [], 'rows' => [], 'max' => CompareService::MAX_ITEMS]);
    }

    /** @return array<int,array<string,mixed>> */
    private function payloadItems(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (isset($payload['items']) && is_array($payload['items'])) {
            return CompareService::normalizeItems($payload['items']);
        }

        if (isset($_POST['items'])) {
            $posted = $_POST['items'];
            if (is_string($posted)) {
                $decoded = json_decode($posted, true);
                if (is_array($decoded)) {
                    return CompareService::normalizeItems($decoded);
                }
            }
            if (is_array($posted)) {
                return CompareService::normalizeItems($posted);
            }
        }

        if (isset($_GET['u'])) {
            return CompareService::parseQueryItems((string)$_GET['u']);
        }

        return [];
    }
}
