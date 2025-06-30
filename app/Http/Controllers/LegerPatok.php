namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegerPatok extends Model
{
    protected $table = 'leger_patok';

    protected $fillable = ['leger_id', 'kode_leger', 'data_patok_id'];
}