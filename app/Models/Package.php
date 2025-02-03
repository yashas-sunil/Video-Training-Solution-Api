<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Khill\Duration\Duration;
use App\Models\Setting;

class Package extends BaseModel
{
    use SoftDeletes;
    const TYPE_FULL = 'full';
    const TYPE_MINI = 'mini';
    const CRASH_COURSE = 'crash';
    const PRE_BOOK = 'pre-book';
    const ALL = 'all';
    // const VALIDITY_IN_MONTHS = 8;
    const VALIDITY_IN_MONTHS = 9;

    const TYPE_CHAPTER_LEVEL = 1;
    const TYPE_SUBJECT_LEVEL = 2;
    const TYPE_CUSTOMIZED = 3;

    protected $dates = ['attempt', 'expire_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'double',
        'discounted_price' => 'double',
        'discounted_price_expire_at' => 'datetime:Y-m-d',
        'special_price' => 'double',
        'special_price_active_from' => 'datetime:Y-m-d',
        'special_price_expire_at' => 'datetime:Y-m-d',
        'is_approved' => 'boolean',
        'is_mini' => 'boolean',
        'is_crash_course' => 'boolean',
        'is_prebook' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
//        'video_duration',
//        'video_duration_formatted',
        'is_special_price_expired',
        'selling_price',
        'discount_percentage',
        'prebook_selling_price',
        'strike_prices',
        'pendrive_selling_price',
        'pendrive_strike_prices',
        'enrolled_count',
        'professors',
        'total_duration_formatted',
        'bonus_duration_formatted',
        'duration_formatted',
        'subjects_and_chapters',
        'is_prebook_package_launched',
        'review_count', 'average_rating'
    ];

    public function getProfessorsAttribute()
    {
        $packageIDs = [];

        if ($this->type == 1) {
            $packageIDs[] = $this->id;
        }

        if ($this->type == 2) {
            $packageIDs = SubjectPackage::where('package_id', $this->id)->get()->pluck('chapter_package_id');
        }

        if ($this->type == 3) {
            $selectedPackageIDs = CustomizedPackage::where('package_id', $this->id)->get()->pluck('selected_package_id');

            foreach ($selectedPackageIDs as $selectedPackageID) {
                $package = Package::find($selectedPackageID);

                if ($package->type == 1) {
                    $packageIDs[] = $package->id;
                }

                if ($package->type == 2) {
                    $chapterPackageIDs = SubjectPackage::where('package_id', $package->id)->get()->pluck('chapter_package_id');

                    foreach ($chapterPackageIDs as $chapterPackageID) {
                        $packageIDs[] = $chapterPackageID;
                    }
                }
            }
        }

        $professorIDs = PackageVideo::whereIn('package_id', $packageIDs)->with('video')->get()->pluck('video.professor_id')->unique();

        $professors = Professor::whereIn('id', $professorIDs)->get();

        return $professors;
    }

    public function getIsSpecialPriceExpiredAttribute() {
        return Carbon::parse($this->special_price_expire_at)->endOfDay()->isPast();
    }

    public function getImageUrlAttribute() {
        if ($this->image) {
            return env('IMAGE_URL').'/packages/'.$this->image;
        }

        return null;
    }

    public function getVideoDurationAttribute() {
        $videos = $this->videos()->sum('duration');

        return $videos;
    }

    public function getVideoDurationFormattedAttribute() {
        $durationInSeconds = $this->video_duration;
        $h = floor($durationInSeconds / 3600);
        $resetSeconds = $durationInSeconds - $h * 3600;
        $m = floor($resetSeconds / 60);
        $resetSeconds = $resetSeconds - $m * 60;
        $s = round($resetSeconds, 3);
        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        $s = str_pad($s, 2, '0', STR_PAD_LEFT);

        if ($h > 0) {
            $duration[] = $h;
        }

        $duration[] = $m;

        $duration[] = $s;

        return implode(':', $duration);
    }

    public function getSpecialPriceAttribute($value) {
        if( $this->special_price_active_from <= Carbon::today() && $this->special_price_expire_at >= Carbon::today())
        {
           return $value;
        }

       return 0;
    }

    public function getDiscountedPriceAttribute($value) {
        if (Carbon::parse($this->discounted_price_expire_at)->endOfDay()->isPast()) {
            return 0;
        }

        return $value;
    }

    public function getSellingPriceAttribute() {
        if ($this->is_prebook && !$this->is_prebook_package_launched) {
            return $this->booking_amount;
        }

        if (! empty($this->special_price) && $this->special_price_active_from <= Carbon::today() && $this->special_price_expire_at >= Carbon::today()) return $this->special_price;
        if (! empty($this->discounted_price) && $this->discounted_price_expire_at >= Carbon::today()) return $this->discounted_price;
        return $this->price;
    }

    public function getPrebookSellingPriceAttribute()
    {
        if ($this->is_prebook) {
            if (! empty($this->special_price) && $this->special_price_active_from <= Carbon::today() && $this->special_price_expire_at >= Carbon::today()) return $this->special_price;
            if (! empty($this->discounted_price) && $this->discounted_price_expire_at >= Carbon::today()) return $this->discounted_price;
            return $this->price;
        }

        return null;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->is_prebook && !$this->is_prebook_package_launched) {
            $percentage = (($this->price - $this->booking_amount)/$this->price)*100;
            return ceil($percentage);
        }
        else{
            if($this->price == $this->selling_price){
                return 0;
            }
            else{
                $percentage = (($this->price - $this->selling_price)/$this->price)*100;
                return ceil($percentage);
            }
        }
    }

    public function getStrikePricesAttribute() {
        $prices = collect([
            $this->price,
            $this->discounted_price,
            $this->special_price
        ])->filter();

        $prices->pop();

        return $prices->all();
    }


    public function getPendriveSpecialPriceAttribute($value) {
        if ($this->pendrive_special_price_expire_at && Carbon::parse($this->pendrive_special_price_expire_at)->endOfDay()->isPast()) {
            return 0;
        }

        return $value;
    }

    public function getPendriveDiscountedPriceAttribute($value) {
        if ($this->pendrive_discounted_price_expire_at && Carbon::parse($this->pendrive_discounted_price_expire_at)->endOfDay()->isPast()) {
            return 0;
        }

        return $value;
    }

    public function getPendriveSellingPriceAttribute() {
        if (! empty($this->pendrive_special_price)) return $this->pendrive_special_price;
        if (! empty($this->pendrive_discounted_price)) return $this->pendrive_discounted_price;
        return $this->pendrive_price;
    }

    public function getPendriveStrikePricesAttribute() {
        $prices = collect([
            $this->pendrive_price,
            $this->pendrive_discounted_price,
            $this->pendrive_special_price
        ])->filter();

        $prices->pop();

        return $prices->all();
    }


    public function getEnrolledCountAttribute() {
        return $this->orderItems()->where('payment_status',OrderItem::PAYMENT_STATUS_FULLY_PAID)->count();
    }

    public function getReviewCountAttribute() {
        return $this->orderItems()->whereNotNull('review')->count();
    }

    public function getAverageRatingAttribute() {

        $rating =  $this->orderItems()->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)->where('review_status', 'ACCEPTED')->sum('rating');
        $total = $this->orderItems()->where('payment_status',OrderItem::PAYMENT_STATUS_FULLY_PAID)->where('review_status', 'ACCEPTED')->whereNotNull('rating')->count();

        $totalRating = 0;
        if($rating>0 && $total>0){
            $totalRating = $rating/$total;
        }

        return $totalRating;
    }

    public function getTotalDurationFormattedAttribute()
    {
        if (!$this->total_duration) {
            return null;
        }

        $durationInSeconds = $this->total_duration;
        $h = floor($durationInSeconds / 3600);
        $resetSeconds = $durationInSeconds - $h * 3600;
        $m = floor($resetSeconds / 60);
        $resetSeconds = $resetSeconds - $m * 60;
        $s = round($resetSeconds, 3);
        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        $s = str_pad($s, 2, '0', STR_PAD_LEFT);

        if ($h >= 0) {
            $duration[] = $h;
        }

        $duration[] = $m;

        $duration[] = $s;

        return implode(':', $duration);
    }

    public function getBonusDurationFormattedAttribute()
    {
        if (!$this->bonus_duration) {
            return null;
        }

        $durationInSeconds = $this->bonus_duration;
        $h = floor($durationInSeconds / 3600);
        $resetSeconds = $durationInSeconds - $h * 3600;
        $m = floor($resetSeconds / 60);
        $resetSeconds = $resetSeconds - $m * 60;
        $s = round($resetSeconds, 3);
        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        $s = str_pad($s, 2, '0', STR_PAD_LEFT);

        if ($h >= 0) {
            $duration[] = $h;
        }

        $duration[] = $m;

        $duration[] = $s;

        return implode(':', $duration);
    }


    public function getDurationFormattedAttribute()
    {
         if($this->duration == 'Unlimited'){
             return 'Unlimited Views';
         } else{
             return $this->duration ." Times Views";
         }


    }

    public function getSubjectsAndChaptersAttribute()
    {
        $packageIDs = [];

        if ($this->type == 1) {
            $packageIDs[] = $this->id;
        }

        if ($this->type == 2) {
            $packageIDs = SubjectPackage::where('package_id', $this->id)->get()->pluck('chapter_package_id');
        }

        if ($this->type == 3) {
            $selectedPackageIDs = CustomizedPackage::where('package_id', $this->id)->get()->pluck('selected_package_id');

            foreach ($selectedPackageIDs as $selectedPackageID) {
                $package = Package::find($selectedPackageID);

                if ($package->type == 1) {
                    $packageIDs[] = $package->id;
                }

                if ($package->type == 2) {
                    $chapterPackageIDs = SubjectPackage::where('package_id', $package->id)->get()->pluck('chapter_package_id');

                    foreach ($chapterPackageIDs as $chapterPackageID) {
                        $packageIDs[] = $chapterPackageID;
                    }
                }
            }
        }

        $packageVideos = PackageVideo::whereIn('package_id', $packageIDs)->with('module', 'video')->get();

        $subjects = Subject::whereIn('id', $packageVideos->pluck('video.subject_id'))->get();

        foreach ($subjects as $subject) {
            $chapters = Chapter::whereIn('id', $packageVideos->pluck('video.chapter_id'))->where('subject_id', $subject->id)->get();

            if (count($subjects) == 1) {
                $subject->chapters = $chapters;
            }
        }

        return $subjects;
    }

    public function getIsPrebookPackageLaunchedAttribute()
    {
        if (! $this->is_prebook) {
            return false;
        }

        return Carbon::parse($this->prebook_launch_date)->startOfDay()->isPast();
    }

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function subject() {
        return $this->belongsTo(Subject::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function chapters() {
        return $this->belongsTo(Chapter::class);
    }

    public function chapterPackages() {
        return $this->belongsToMany(Package::class, 'subject_packages', 'package_id', 'chapter_package_id')
            ->withPivot('chapter_package_order')->withTimestamps();
    }

    public function selectedPackages() {
        return $this->belongsToMany(Package::class, 'customized_packages', 'package_id', 'selected_package_id');
    }

    public function packages() {
        return $this->belongsToMany(Package::class, 'subject_packages', 'chapter_package_id', 'package_id')
            ->withPivot('chapter_package_order')->withTimestamps();
    }

    public function level() {
        return $this->belongsTo(Level::class);
    }

    public function language() {
        return $this->belongsTo(Language::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'approved_user_id');
    }


    public function orderItems() {
        return $this->hasMany('App\Models\OrderItem');
    }

    public function wishlist()
    {
        return $this->hasOne('App\Models\Wishlist');
    }

    public function videos() {
        return $this->belongsToMany(Video::class,'package_videos','package_id', 'video_id');
    }

    public function video() {
        return $this->belongsTo(Video::class);
    }

    public function packageVideos()
    {
        return $this->hasMany(PackageVideo::class);
    }

    public function packagetype() {
        return $this->belongsTo(PackageType::class, 'package_type');
    }

    public function subjectPackages()
    {
        return $this->hasMany(SubjectPackage::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query) {
        return $query->where('is_approved', true);
    }

    public function packageStudyMaterials()
    {
        return $this->hasMany(PackageStudyMaterial::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type) {
        if ($type == Package::TYPE_MINI) {
            return $query->where('is_mini', true)->where('is_crash_course', false);
        }

        if ($type == Package::TYPE_FULL) {
            return $query->where('is_mini', false)->where('is_crash_course', false);
//                ->where('is_prebook', false);
        }

        if ($type == Package::CRASH_COURSE) {
            return $query->where('is_crash_course', true)->where('is_mini', false);
        }
        if ($type == Package::PRE_BOOK) {
            return $query->where('is_prebook', true)
                ->whereDate('prebook_launch_date', '>', Carbon::today());
        }
        if ($type == Package::ALL) {
            return $query;
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  integer  $courseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCourse($query, $courseId)
    {
        if ($courseId) {
            return $query->where('course_id', $courseId);
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  integer  $levelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfLevel($query, $levelId)
    {
        if ($levelId) {
            return $query->where('level_id', $levelId);
        }

        return $query;
    }

    public function scopeOfLevels($query, $levels)
    {
        if ($levels) {
            return $query->whereIn('level_id', $levels);
        }

        return $query;
    }

    public function scopeofLanguages($query, $languages)
    {
        if ($languages) {
            return $query->whereIn('language_id', $languages);
        }

        return $query;
    }

    public function scopeofPackagetypes($query,$packagetypes)
    {
       if($packagetypes){
            return $query->whereIn('package_type', $packagetypes);
       } 
       return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  integer $subjectId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfSubject($query, $subjectId)
    {
        if ($subjectId) {
            return $query->where('subject_id', $subjectId);
        }

        return $query;
    }

    public function scopeOfSubjects($query, $subjects)
    {
        if ($subjects) {
            return $query->whereIn('subject_id', $subjects);
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  integer $chapterId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfChapter($query, $chapterId)
    {
        if ($chapterId) {
            return $query->whereHas('videos.chapter', function($query) use($chapterId) {
                $query->where('id', $chapterId);
            });
        }

        return $query;
    }

    public function scopeOfChapters($query, $chapters)
    {
        if ($chapters) {
            return $query->whereHas('videos.chapter', function($query) use($chapters) {
                $query->where('id', $chapters);
            });
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  integer $professorID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfProfessor($query, $professorID)
    {
        if ($professorID) {

            $videoIDs = Video::where('professor_id', $professorID)->get()->pluck('id');
            $chapterPackageIDs = PackageVideo::whereIn('video_id', $videoIDs)->get()->pluck('package_id')->unique();
            $subjectPackageIDs = SubjectPackage::whereIn('chapter_package_id', $chapterPackageIDs)->get()->pluck('package_id')->unique();

            $packageIDs = [];

            foreach($chapterPackageIDs as $chapterPackageID) {
                $packageIDs[] = $chapterPackageID;
            }

            foreach($subjectPackageIDs as $subjectPackageID) {
                $packageIDs[] = $subjectPackageID;
            }

            $query->whereIn('id', $packageIDs);
        }

        return $query;
    }

    public function scopeOfProfessors($query, $professors)
    {
        if ($professors) {

            $videoIDs = Video::whereIn('professor_id', $professors)->get()->pluck('id');
            $chapterPackageIDs = PackageVideo::whereIn('video_id', $videoIDs)->get()->pluck('package_id')->unique();
            $subjectPackageIDs = SubjectPackage::whereIn('chapter_package_id', $chapterPackageIDs)->get()->pluck('package_id')->unique();

            $packageIDs = [];

            foreach($chapterPackageIDs as $chapterPackageID) {
                $packageIDs[] = $chapterPackageID;
            }

            foreach($subjectPackageIDs as $subjectPackageID) {
                $packageIDs[] = $subjectPackageID;
            }

            $query->whereIn('id', $packageIDs);
        }

        return $query;
    }

    public function scopeOfLanguage($query, $languageId)
    {
        if ($languageId) {
            return $query->where('language_id', $languageId);
        }
        return $query;
    }


    /**
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeOfSearch($query, $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere(function ($query) use ($search) {
                    $query->whereHas('course', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                })->orWhere(function ($query) use ($search) {
                    $query->whereHas('level', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                })->orWhere(function ($query) use ($search) {
                    $query->whereHas('subject', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                })->orWhere(function ($query) use ($search) {
                    $query->whereHas('chapter', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                })->orWhere(function ($query) use ($search) {
                    $query->whereHas('chapterPackages', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                })->orWhere(function ($query) use ($search) {
                    $query->whereHas('selectedPackages', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                });
        });
    }

    /**
     * @param Builder $query
     * @param bool $inRandomOrder
     * @return Builder
     */
    public function scopeOfRandom($query, $inRandomOrder)
    {
        if (!$inRandomOrder) {
            return $query;
        }

        return $query->inRandomOrder();
    }

    /**
     * @param Builder $query
     * @param boolean $isActive
     * @return Builder
     */
    public function scopeOfActive($query, $isActive)
    {
        if (!$isActive) {
            return $query;
        }

        return $query->whereDate('expire_at', '>=', Carbon::today())->orWhere('expire_at',NULL);
    }

    public function scopeofRatings($query, $ratings)
    {
        if (!$ratings) {
            return $query;
        }

        $minRating = collect($ratings)->min();
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeofPublished($query)
    {
        return $query->where('is_approved', 1);
    }
    public function scopeofArchived($query)
    {
         $query->where('is_archived',0);
         return  $query->orWhere('is_archived',NULL);
    }

    public function scopeofPrice($query, $price)
    {
        if(!$price){
            return $query;
        }

        if($price == 'low'){
            return $query->orderBy('selling_amount', 'asc');
        }

        if($price == 'high'){
            return $query->orderBy('selling_amount', 'desc');
        }

          /************Modified BY TE **********/
          if($price == 'oldest'){
            return $query->orderBy('created_at', 'asc');
        }
        if($price == 'newest'){
            return $query->orderBy('created_at', 'desc');
        }

        // if($price == 'special'){
        //     return $query->where('special_price','>','0')->where('special_price_active_from','<=',Carbon::today())
        //                  ->where('special_price_expire_at','>=',Carbon::today());

        // }
        /**************************************/
        return $query;

    }
 /************************Added by TE************/
 public function scopeofPopularity($query,$price){
    if(!$price){
        return $query;
    }

    if($price == 'popular'){  
            return $query ->withCount(['orderItems as total_buy' => function ($query) {
                    $query->groupBy('order_items.package_id');
                }])->orderBy('total_buy', 'DESC');

    }
    if($price== 'low_popular'){
        return $query ->withCount(['orderItems as total_buy' => function ($query) {
            $query->groupBy('order_items.package_id');
        }])->orderBy('total_buy', 'ASC');
    }
}

public function scopeofDemo($query,$linked_packages_id){
    if(!$linked_packages_id){
        return $query;
    }
   return $query->whereIn('id',$linked_packages_id);
}
/************TE ENDS****************************/

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfNotPreBooked($query)
    {
        if (auth('api')->id()) {
            $prebookedIDs = OrderItem::query()
                ->where('user_id', auth('api')->id())
                ->where('payment_status', OrderItem::PAYMENT_STATUS_PARTIALLY_PAID)
                ->orWhere('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
                ->where('is_prebook', true)
                ->whereHas('package', function($query) {
                    $query->whereDate('prebook_launch_date', '>', Carbon::today());
                })
                ->get()->pluck('package_id');

            if ($prebookedIDs) {
                return $query->whereNotIn('id', $prebookedIDs);
            }
        }

        return $query;
    }

    public static function getAll(
        $type = null,
        $courseId = null,
        $levelId = null,
        $subjectId = null,
        $chapterId = null,
        $professorId = null,
        $languageId = null,
        $search= null,
        $page = null,
        $limit = null,
        $inRandomOrder = false,
        $price = null,
        $levels = null,
        $languages = null,
        $subjects = null,
        $chapters = null,
        $professors = null,
        $ratings = null
    ) {
        $page = $page ?: 1;
      //  $limit = $limit ?: 10;
         $limit = $limit ?: 3; //Added by TE 

         $query =  Package::with([
             'language',
            'course',
            'level','videos','orderItems' => function($query){
                $query->where('review_status', 'ACCEPTED');
            }]
//            'subject.chapters'
//            'videos.professor'
        )
            ->approved()
            ->ofNotPreBooked()
            ->ofActive(true)
            ->ofPublished()
            ->ofRandom($inRandomOrder)
            ->ofType($type)
            ->ofCourse($courseId)
            ->ofLevel($levelId)
            ->ofLevels($levels)
            ->ofSubject($subjectId)
            ->ofSubjects($subjects)
            ->ofChapter($chapterId)
            ->ofChapters($chapters)
            ->ofProfessor($professorId)
            ->ofProfessors($professors)
            ->ofLanguage($languageId)
            ->ofLanguages($languages)
            ->ofSearch($search)
            ->ofPrice($price)
            ->ofRatings($ratings)
            ->ofArchived()
            ->orderBy('selling_amount', 'desc')
            ->orderBy('type', 'desc')
            ->orderBy('name', 'asc')
            ->paginate($limit, ['*'], 'page', $page);
           // ->get();

        return $query;
    }

    public function videoHistories()
    {
        return $this->hasMany(VideoHistory::class, 'package_id');
    }


    public static function getPackageList(
        $search = null,
        $courseId = null,
        $levelId = null,
        $page = null,
        $limit = null,
        $inRandomOrder = false,
        $price = null,
        $professorId = null,
        $levels = null,
        $languages = null,
        $subjects = null,
        $linked_packages_id= null,
        $chapters = null,
        $professors = null,
        $ratings = null,
        $packagetypes = null,
        $offer=null
    ) {
        $page = $page ?: 1;
        $limit = $limit ?: 10;

        $query =  Package::with([
            'language','packagetype','orderItems' => function($query){
                $query->where('review_status', 'ACCEPTED');
            }])
            ->approved()
            ->ofNotPreBooked()
            ->ofActive(true)
            ->ofPublished()
            ->ofRandom($inRandomOrder)
            ->ofLevels($levels)
            ->ofCourse($courseId)
            ->ofLevel($levelId)
            ->ofSubjects($subjects)
            ->ofChapters($chapters)
            ->ofProfessor($professorId)
            ->ofProfessors($professors)
            ->ofLanguages($languages)
            ->ofSearch($search)
            ->ofPrice($price)
            ->ofRatings($ratings)
            ->ofOffer($offer)
            ->ofPackagetypes($packagetypes)
            ->ofPopularity($price)
            ->ofDemo($linked_packages_id)
            ->ofArchived()
            ->orderBy('selling_amount', 'desc')
            ->orderBy('name', 'asc')
            ->paginate($limit, ['*'], 'page', $page);
            //->get();

        return $query;
    }

    public function sectionPackages()
    {
        return $this->belongsToMany(Section::class, 'section_packages', 'package_id','section_id');
    }
    public function scopeofOffer($query, $offer)
    {
        $new_realse = Setting::where('key', "new_release")->first();
      
         $reale_setting=$new_realse['value'];
        if ($offer=='new') {
             $query->where('published_at','>=', Carbon::now()->subDays($reale_setting))
             ->where('type','!=',Package::TYPE_CHAPTER_LEVEL);
        }
     
        if ($offer=='special') {
            return $query->where('special_price','>','0')->where('special_price_active_from','<=',Carbon::today())
            ->where('special_price_expire_at','>=',Carbon::today());
        }

        if ($offer=='combo') {
            
            return $query->where('type','=',Package::TYPE_CUSTOMIZED);
        }

        return $query;
    }
}
