<?php

namespace App\Http\Controllers\V1;

use App\BlogLike;
use App\Models\Blog;
use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $blogs = Blog::query();
        $blogs->ofPublished();
        $blogs->ofOrder(request()->input('order'));

        if (request()->filled('category_id')) {
            $blogs->ofCategory(request()->input('category_id'));
        }

        if (request()->filled('tag_id')) {
            $blogs->ofTag(request()->input('tag_id'));
        }

        if (request()->filled('search')) {
            $blogs->ofSearch(request()->input('search'));
        }

        $data = $blogs->with('category')->paginate();

        return $this->jsonResponse('Blogs', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $userId = auth('api')->id() ?? null;
        $blog = Blog::query()->with('likes');
        $blog->ofPublished();
        $blog = $blog->where('slug', $id)->with('category', 'blogTags', 'relatedBlogs')->first();

        if ($blog) {
            $blog->views = $blog->views + 1;
            $blog->save();
        }

        return $this->jsonResponse('Blog', ['blog'=> $blog, 'userId' => $userId]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function like(int $id)
    {
        $blogLike = BlogLike::query()
            ->where('blog_id', $id)
            ->where('user_id', auth('api')->id())
            ->first();

        if ($blogLike) {
            $blogLike->delete();
            $totalLikes = BlogLike::query()
                ->where('blog_id', $id)
                ->count();

            return $this->jsonResponse('Blog successfully unliked', [
                'is_liked' => false,
                'total_likes' => $totalLikes
            ]);
        } else {
            $blogLike = new BlogLike();
            $blogLike->blog_id = $id;
            $blogLike->user_id = auth('api')->id();
            $blogLike->save();

            $totalLikes = BlogLike::query()
                ->where('blog_id', $id)
                ->count();

            return $this->jsonResponse('Blog successfully liked', [
                'is_liked' => true,
                'total_likes' => $totalLikes
            ]);
        }
    }
}
