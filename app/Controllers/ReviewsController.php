<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Review;
use App\Models\ReviewFacebookPost;
use App\Models\ReviewVideo;
use App\Models\Setting;

class ReviewsController extends Controller
{
    public function index(): void
    {
        $reviewVideos    = ReviewVideo::activeOrdered(24);
        $facebookPosts   = ReviewFacebookPost::activeOrdered(12);
        $facebookAppId   = trim((string) Setting::get('facebook_plugins_app_id', ''));
        $reviews         = Review::latest(24);

        $this->view('reviews/index', [
            'meta_title'       => 'รีวิวและวิดีโอแนะนำ — แพกาญ.com',
            'meta_description' => 'รวมรีวิวจากผู้เข้าพักจริง วิดีโอ YouTube และโพสต์จากเพจ Facebook ที่คัดแล้ว — แพและที่เที่ยวกาญจนบุรี',
            'meta_canonical'   => url('/reviews'),
            'reviewVideos'     => $reviewVideos,
            'facebookPosts'    => $facebookPosts,
            'facebookAppId'    => $facebookAppId,
            'reviews'          => $reviews,
        ]);
    }
}
