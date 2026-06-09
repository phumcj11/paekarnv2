<?php



/** @var array<int,array<string,mixed>> $reviewVideos */



/** @var array<int,array<string,mixed>> $facebookPosts */



/** @var string $facebookAppId */



/** @var array<int,array<string,mixed>> $reviews */



use App\Models\ReviewVideo;



$videoGroups     = ReviewVideo::partitionByOrientation($reviewVideos ?? []);

$landscapeVideos = $videoGroups['landscape'];

$portraitVideos  = $videoGroups['portrait'];

$hasLandscape    = $landscapeVideos !== [];

$hasPortrait     = $portraitVideos !== [];



?>



<section class="bg-primary-700 text-white">



  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 md:py-12">



    <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2"><i data-lucide="message-circle" class="w-7 h-7"></i> รีวิวและวิดีโอแนะนำ</h1>



    <p class="text-white/85 mt-2 max-w-2xl leading-relaxed">



      ทั้งเสียงจากผู้เข้าพักที่พักจริง วิดีโอรีวิว YouTube คลิปแนวตั้งจาก Shorts / TikTok / Reels และโพสต์จากเพจ Facebook ที่เราคัดให้



    </p>



    <div class="flex flex-wrap gap-3 mt-6">



      <?php if ($hasLandscape): ?>



      <a href="#reviews-youtube" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-semibold transition scroll-smooth">



        <?php \App\Core\View::partial('partials/brand-icon', ['name' => 'youtube']); ?> วิดีโอรีวิว



      </a>



      <?php endif; ?>



      <?php if ($hasPortrait): ?>



      <a href="#reviews-shorts" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-semibold transition scroll-smooth">



        <i data-lucide="smartphone" class="w-4 h-4"></i> คลิปแนวตั้ง



      </a>



      <?php endif; ?>



      <?php if (!empty($facebookPosts)): ?>



      <a href="#reviews-facebook" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-semibold transition scroll-smooth">



        <?php \App\Core\View::partial('partials/brand-icon', ['name' => 'facebook']); ?> โพสต์ Facebook



      </a>



      <?php endif; ?>



      <a href="#reviews-home" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-primary-800 hover:bg-white/95 text-sm font-semibold transition scroll-smooth">



        <i data-lucide="users" class="w-4 h-4"></i> รีวิวจากผู้พัก



      </a>



    </div>



  </div>



</section>







<?php if ($hasLandscape): ?>



<?php \App\Core\View::partial('partials/review_videos_grid', [



    'videos'       => $landscapeVideos,



    'sectionId'    => 'reviews-youtube',



    'eyebrow'      => 'YouTube',



    'title'        => 'วิดีโอรีวิว',



    'subtitle'     => 'คลิปรีวิวแพ ที่พัก และที่เที่ยวกาญจนบุรี — กดเล่นเมื่อพร้อม',



    'moreUrl'      => url('/videos'),



    'sectionClass' => 'bg-cloud border-b border-slate-100 py-14',



    'with_anchor'  => true,



]); ?>



<?php endif; ?>







<?php if ($hasPortrait): ?>



<?php \App\Core\View::partial('partials/review_videos_carousel', [



    'videos'       => $portraitVideos,



    'sectionId'    => 'reviews-shorts',



    'eyebrow'      => 'Short clips',



    'title'        => 'คลิปรีวิวแนวตั้ง',



    'subtitle'     => 'YouTube Shorts · TikTok · Instagram Reels — เลื่อนดูคลิปที่คัดแล้ว',



    'moreUrl'      => url('/videos'),



    'sectionClass' => 'bg-white border-b border-slate-100 py-14',



    'with_anchor'  => true,



]); ?>



<?php endif; ?>







<?php \App\Core\View::partial('partials/facebook_review_posts', [



    'facebookPosts' => $facebookPosts,



    'facebookAppId' => $facebookAppId,



]); ?>







<?php



if (!empty($reviews)) {



    \App\Core\View::partial('partials/guest_review_cards_section', [



        'reviews'             => $reviews,



        'section_extra_class' => '',



        'section_subtitle'    => 'รีวิวเหล่านี้มาจากผู้เข้าพักที่ได้จองผ่านระบบหรือเข้าพักจริง — คลิกเพื่อดูหน้าที่พัก',



    ]);



} else {



    ?>



<section id="reviews-home" class="bg-white border-y border-slate-100 py-14 scroll-mt-28 md:scroll-mt-36">



  <div class="max-w-7xl mx-auto px-4 text-center text-slate-600 text-sm">



    ยังไม่มีรีวิวจากผู้เข้าพักในขณะนี้



  </div>



</section>



    <?php



}



?>







<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10">



  <div class="flex flex-wrap justify-center gap-4 text-sm">



    <a href="<?= url('/properties') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700 transition">



      <i data-lucide="hotel" class="w-4 h-4"></i> ค้นหาที่พัก



    </a>



    <a href="<?= url('/blog') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50 transition">



      <i data-lucide="newspaper" class="w-4 h-4"></i> บทความท่องเที่ยว



    </a>



  </div>



</section>


