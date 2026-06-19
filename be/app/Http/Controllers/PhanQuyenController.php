use Illuminate\Database\Seeder;
use App\Models\PhanQuyen;
use App\Models\VaiTro;
use App\Models\ChucNang;

class PhanQuyenSeeder extends Seeder
{
public function run(): void
{
$admin = VaiTro::where('ten_vai_tro', 'admin')->first();

if (!$admin) return;

$chucNangs = ChucNang::all();

foreach ($chucNangs as $cn) {
PhanQuyen::updateOrCreate([
'vai_tro_id' => $admin->id,
'chuc_nang_id' => $cn->id,
]);
}
}
}