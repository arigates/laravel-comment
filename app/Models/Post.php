<?php

namespace App\Models;

use BeyondCode\Comments\Contracts\Commentator;
use BeyondCode\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasComments;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'media',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attach a comment to this model as a specific user.
     *
     * @param Model|null $user
     * @param string $comment
     * @param string $media
     * @return Model
     */
    public function commentAsUserWithMedia(?Model $user, string $comment, string $media): Model
    {
        $commentClass = config('comments.comment_class');

        $comment = new $commentClass([
            'comment' => $comment,
            'media' => $media,
            'is_approved' => $user instanceof Commentator && !$user->needsCommentApproval($this),
            'user_id' => is_null($user) ? null : $user->getKey(),
            'commentable_id' => $this->getKey(),
            'commentable_type' => get_class(),
        ]);

        return $this->comments()->save($comment);
    }
}
