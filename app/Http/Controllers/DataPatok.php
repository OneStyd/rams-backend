namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPatok extends Model
{
    protected $table = 'data_patok';

    protected $fillable = [
        'nomor_patok', 'nomor_ruas', 'nomor_seksi', 'nama_ruas',
        'desa', 'kecamatan', 'kabupaten', 'provinsi',
        'lokasi', 'tahun_pemasangan',
        'koordinat_x', 'koordinat_y', 'koordinat_z',
        'sistem_koordinat', 'tipe_alat_ukur', 'tanggal_pengukuran', 'keterangan'
    ];
}
