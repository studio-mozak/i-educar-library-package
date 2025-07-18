<?php

use App\Models\LegacySchoolClass;
use App\Models\LegacyGrade;
use App\Models\LegacyStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;


require_once 'include/pmieducar/clsPmieducarBiblioteca.inc.php';
require_once 'include/pmieducar/clsPmieducarBibliotecaUsuario.inc.php';
require_once 'include/pmieducar/clsPmieducarAcervo.inc.php';
require_once 'include/pmieducar/clsPmieducarCliente.inc.php';

return new class extends clsListagem
{
    private $totalBibliotecas;
    private $totalObras;
    private $totalClientes;

    public function __construct()
    {
        $this->setTotals();
    }

    private function setTotals()
    {
        $logged_user = Session::get('logged_user');
        $role = $logged_user->role ?? '';
        $userId = $logged_user->personId;

        if ($role === 'Administrador') {
            $this->totalBibliotecas = DB::table('pmieducar.biblioteca')->where('ativo', 1)->count();
            $this->totalObras = DB::table('pmieducar.acervo')->where('ativo', 1)->count();
            $this->totalClientes = DB::table('pmieducar.cliente')->where('ativo', 1)->count();
        } else {
            $escolas_usuario = [];
            if (isset($logged_user->schools) && is_array($logged_user->schools)) {
                foreach ($logged_user->schools as $school) {
                    if (is_object($school) && isset($school->id)) {
                        $escolas_usuario[] = $school->id;
                    } elseif (is_array($school) && isset($school['id'])) {
                        $escolas_usuario[] = $school['id'];
                    } elseif (is_object($school) && isset($school->cod_escola)) {
                        $escolas_usuario[] = $school->cod_escola;
                    } elseif (is_array($school) && isset($school['cod_escola'])) {
                        $escolas_usuario[] = $school['cod_escola'];
                    }
                }
            }
            if (empty($escolas_usuario)) {
                $this->totalBibliotecas = 0;
                $this->totalObras = 0;
                $this->totalClientes = 0;
                return;
            }
            $bibliotecas = DB::table('pmieducar.biblioteca')
                ->where('ativo', 1)
                ->whereIn('ref_cod_escola', $escolas_usuario)
                ->pluck('cod_biblioteca');
            $this->totalBibliotecas = $bibliotecas->count();

            if ($bibliotecas->isEmpty()) {
                $this->totalObras = 0;
                $this->totalClientes = 0;
                return;
            }
            $this->totalObras = DB::table('pmieducar.acervo')
                ->where('ativo', 1)
                ->whereIn('ref_cod_biblioteca', $bibliotecas)
                ->count();
            $this->totalClientes = DB::table('pmieducar.cliente_tipo_cliente')
                ->whereIn('ref_cod_biblioteca', $bibliotecas)
                ->distinct('ref_cod_cliente')
                ->count('ref_cod_cliente');
        }
    }

    public function RenderHTML()
    {
        $this->breadcrumb(
            currentPage: 'Dashboard',
            breadcrumbs: [
                '/intranet/educar_index.php' => 'Escola'
            ]
        );
        
        return '
            <h1 class="title_ensinus">Módulo <strong>Biblioteca</strong></h1>
            <div>
                <div class="row">
                    ' . $this->renderCard('TOTAL DE BIBLIOTECAS', $this->totalBibliotecas) . '
                    ' . $this->renderCard('TOTAL DE OBRAS', $this->totalObras) . '
                    ' . $this->renderCard('TOTAL DE CLIENTES', $this->totalClientes) . '
                </div>
            </div>';
    }

    private function renderCard(string $titulo, int $valor): string
    {
        return '
        <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4">
            <div class="height-150 card-ensinus border-radius-8">
                <div class="card-body p-3 h-100 d-flex justify-content-around align-items-center">
                    <div class="row w-100">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">' . $titulo . '</p>
                                <h5 class="font-weight-bolder">' . $valor . '</h5>
                            </div>
                        </div>
                        <div class="col-4 text-end d-flex justify-content-end align-items-center">
                            <div class="d-flex justify-content-center align-items-center icontainer-icon bg-blur-ensinus shadow-primary text-center rounded-circle">
                                <img src="/assets/img/users-couple-svgrepo-com.svg">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }

    public function Formular()
    {
        $this->title = 'Biblioteca';
        $this->processoAp = 57;
    }
};
