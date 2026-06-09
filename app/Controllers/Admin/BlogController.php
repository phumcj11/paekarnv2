<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll("SELECT * FROM blog_posts ORDER BY id DESC");
        View::render('admin/blog/index', [
            'page_title' => 'บล็อก', 'rows' => $rows,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/blog/form', [
            'page_title' => 'เพิ่มบทความ',
            'post' => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validate([
            'title'   => 'required|max:180',
            'slug'    => 'required|max:180',
            'excerpt' => 'max:500',
        ]);
        try { $img = Upload::image('cover_image', 'blog'); } catch (\Throwable $e) { $img = null; }
        $id = BlogPost::create([
            'slug'        => $data['slug'],
            'title'       => $data['title'],
            'excerpt'     => $data['excerpt'] ?? null,
            'content'     => $_POST['content'] ?? '',
            'category'    => $_POST['category'] ?? null,
            'tags'        => $_POST['tags'] ?? null,
            'cover_image' => $img,
            'author_id'   => Auth::id(),
            'meta_title'  => $_POST['meta_title'] ?? null,
            'meta_description' => $_POST['meta_description'] ?? null,
            'status'      => $_POST['status'] ?? 'draft',
            'published_at'=> ($_POST['status'] ?? 'draft') === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
        Session::flash('success', 'บันทึกบทความเรียบร้อย');
        redirect(url('/admin/blog/' . $id . '/edit'));
    }

    public function edit(int $id): void
    {
        $post = BlogPost::find($id);
        if (!$post) { http_response_code(404); View::render('errors/404'); return; }
        View::render('admin/blog/form', [
            'page_title' => 'แก้ไขบทความ', 'post' => $post,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $data = $this->validate([
            'title' => 'required|max:180',
            'slug'  => 'required|max:180',
        ]);
        $update = [
            'slug'        => $data['slug'],
            'title'       => $data['title'],
            'excerpt'     => $_POST['excerpt'] ?? null,
            'content'     => $_POST['content'] ?? '',
            'category'    => $_POST['category'] ?? null,
            'tags'        => $_POST['tags'] ?? null,
            'meta_title'  => $_POST['meta_title'] ?? null,
            'meta_description' => $_POST['meta_description'] ?? null,
            'status'      => $_POST['status'] ?? 'draft',
        ];
        try {
            $img = Upload::image('cover_image', 'blog');
            if ($img) $update['cover_image'] = $img;
        } catch (\Throwable $e) {}

        if (($_POST['status'] ?? '') === 'published') $update['published_at'] = date('Y-m-d H:i:s');
        BlogPost::update($id, $update);
        Session::flash('success','อัปเดตบทความเรียบร้อย');
        redirect(url('/admin/blog/' . $id . '/edit'));
    }

    public function delete(int $id): void
    {
        BlogPost::destroy($id);
        Session::flash('success', 'ลบบทความเรียบร้อย');
        redirect(url('/admin/blog'));
    }
}
