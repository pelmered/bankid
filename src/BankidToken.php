<?php

namespace LJSystem\BankID;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * LJSystem\BankID\BankidToken
 *
 * @property string $token
 * @property string $order_ref
 * @property string $user_uuid
 * @property string $signed_by_name
 * @property string $signed_by_pnr
 * @property string|null $signable_type
 * @property int|null $signable_id
 * @property string $action
 * @property int $used
 * @property int $revoked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property mixed $uuid
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $signable
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereOrderRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereRevoked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereSignableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereSignableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereSignedByName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereSignedByPnr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\LJSystem\BankID\BankidToken whereUserUuid($value)
 * @mixin \Eloquent
 */
class BankidToken extends Model
{
    protected $dates = ['expires_at'];

    /**
     * primaryKey
     *
     * @var integer
     * @access protected
     */
    protected $primaryKey = 'token';
    public $incrementing  = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->user_uuid  = Auth::check() ? Auth::user()->uuid : null;
            $model->token      = uniqid(base64_encode(str_random(60)), true);
            $model->expires_at = Carbon::now()->addMinutes(10)->toDateTimeString();
        });
    }


    public function signable()
    {
        return $this->morphTo();
    }
}
