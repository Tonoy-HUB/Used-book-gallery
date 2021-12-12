<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Type;
use App\Models\Book;
use App\Models\Varsity;
use App\Models\Post;


use Auth;
use Image;
use File;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
   
    public function books_index(){
        $books = Book::orderBy('created_at', 'desc')->where('confirmed', true)->paginate(6);
        $types = Type::all();
        $varsities = Varsity::all();
        return view('user.books_index', compact('books', 'types', 'varsities'));
    }
    public function book_index(){
        $user = Auth::user()->id;
        $books = Book::where('user', $user)->paginate(10);
        return view('user.book_index', compact('books'));
    }
    public function book_show($id){
        $book = Book::find($id);
        return view('user.book_show', compact('book'));
    }
    public function book_edit($id){
        $book = Book::find($id);
        $types = Type::all();
        return view('user.book_edit', compact('book', 'types'));
    }
    public function book_update(Request $request, $id){
        $book = Book::find($id);
        $oldImg = $book->image;
        $book_user = $book->user;
        $newC = $request->input('category');
        $oldC = $book->category;
        if(Auth::user()->id == $book_user){
            $this->validate($request, [
                'name' => 'required',
                'price' => 'required',
                'number' => 'required|max:11|min:10'
            ]);
            //image validation
            if($request->hasFile('image')){
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $file_name = time().'.'.$extension;
                Image::make($file)->resize(700, 400)->save(public_path('/contents/images/book/'.$file_name));
                if($oldImg !== 'no_image.png'){
                    File::delete(public_path('/contents/images/book/'.$oldImg));
                }
            }else{
                $file_name = $oldImg;
            }
            //category validation
            if($newC !== 'null'){
                $category = $newC;
            }else{
                $category = $oldC;
            }
            $book->name = $request->input('name');
            $book->author = $request->input('author');
            $book->price = $request->input('price');
            $book->description = $request->input('description');
            $book->category = $category;
            $book->image = $file_name;
            $book->save();
            return redirect()->route('user.book_index')->with('warning', 'Successfully Updated');

        }else{
            return redirect()->route('user.book_index')->with('error', 'Operation Failed');
        }
    }
    public function book_create(){
        if(Auth::user()->config == 0){
            $types = Type::all();
            return view('user.book_create', compact('types'));
        }else{
            return redirect()->route('home')->with('error', 'You are not registered by admin');
        }
    }
    public function book_store(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'price' => 'required',
            'category' => 'required',
            'number' => 'required|max:11|min:10'
        ]);
        if($request->hasFile('image')){
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            Image::make($file)->resize(700, 400)->save(public_path('/contents/images/book/'.$file_name));
        }else{
            $file_name = 'no_image.png';
        }

        $book = new Book;
        $book->name = $request->input('name');
        $book->author = $request->input('author');
        $book->price = $request->input('price');
        $book->category = $request->input('category');
        $book->description = $request->input('description');
        $book->number = $request->input('number');
        $book->varsity = Auth::user()->varsity;
        $book->image = $file_name;
        $book->user = Auth::user()->id;
        $book->confirmed = false;
        $book->save();
        return redirect()->route('user.book_index')->with('success', 'Successfully Created');
    }
    public function book_destroy($id){
        $book = Book::find($id);
        $oldImg = $book->image;
        if($oldImg != 'no_image.png'){
            File::delete(public_path('/contents/images/book/'.$oldImg));
        }
        $book->delete();
        return redirect()->route('user.book_index')->with('error', 'Removed');
    }
    public function post_index(){
        //$posts = Post::orderBy('id', 'desc')->where('user_id', Auth::user()->id)->paginate(10);
        $posts = Auth::user()->posts;
        return view('user.post_index', compact('posts'));
    }
    public function post_create(){
        return view('user.post_create');
    }
    public function post_store(Request $request){
        $this->validate($request, [
            'book_name' => 'required',
            'number' => 'required|max:11',
            'body' => 'required',
            'author' => 'required'
        ]);
        $post = new Post;
        $post->user_id = Auth::user()->id;
        $post->book_name = $request->input('book_name');
        $post->author = $request->input('author');
        $post->number = $request->input('number');
        $post->body = $request->input('body');
        $post->save();
        return redirect()->route('user.post_index')->with('success', 'Successfully Created');        
    }
    public function post_show($id){
        $post = Post::find($id);
        return view('user.post_show', compact('post'));
    }
    public function post_destroy($id){
        Post::find($id)->delete();
        return redirect()->route('user.post_index')->with('error', 'Successfully Removed');
    }
    public function post_edit($id){
        $post = Post::find($id);
        return view('user.post_edit', compact('post'));
    }
    public function post_update(Request $request, $id){
        $this->validate($request, [
            'book_name' => 'required',
            'number' => 'required|max:11',
            'body' => 'required',
            'author' => 'required'
        ]);
        $post = Post::find($id);
        $post->book_name = $request->input('book_name');
        $post->author = $request->input('author');
        $post->number = $request->input('number');
        $post->body = $request->input('body');
        $post->save();
        return redirect()->route('user.post_index')->with('warning', 'Successfully Updated'); 
    }
}
