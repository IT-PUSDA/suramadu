<?php
// Central mapping & helpers for bidang codes.
// Provides: resolve_bidang_code_from_session(), print_bidang_options($locked)
// Usage: Include where nomor surat / form bidang selection needed.

if (!function_exists('resolve_bidang_code_from_session')) {
    function resolve_bidang_code_from_session(): ?string {
        if (!isset($_SESSION)) { return null; }
        $u = strtoupper($_SESSION['username'] ?? '');
        $n = strtoupper($_SESSION['nama'] ?? '');
        // Map kode -> tokens (any token contained in username or nama matches)
        // Map individual tokens -> kode bidang. Use token keys so duplicates
        // (multiple UPTs with same base code) are allowed.
        $TOKEN_MAP = [
            'SEKRETARIAT'      => '104.1',
            'TU'               => '104.1',
            'ADMIN_SEKRETARIAT'=> '104.1',

            'PSDA'             => '104.2',
            'ADMIN_PSDA'       => '104.2',

            'SWP'              => '104.3',
            'ADMIN_SWP'        => '104.3',

            'IRIGASI'          => '104.4',
            'ADMIN_IRIGASI'    => '104.4',

            'BINFAT'           => '104.5',
            'ADMIN_BINFAT'     => '104.5',

            'KEDIRI'           => '104.6.02',
            'ADMIN_KEDIRI'     => '104.6.02',

            'BOJONEGORO'       => '104.6.05',
            'ADMIN_BOJONEGORO' => '104.6.05',

            'BONDOWOSO'        => '104.6.06',
            'ADMIN_BONDOWOSO'  => '104.6.06',

            'LUMAJANG'         => '104.6.07',
            'ADMIN_LUMAJANG'   => '104.6.07',

            'PASURUAN'         => '104.6.08',
            'ADMIN_PASURUAN'   => '104.6.08',

            'MADURA'           => '104.6.09',
            'ADMIN_MADURA'     => '104.6.09',
        ];

        // Check username and display name tokens against the token map.
        // Prefer matching more specific / longer tokens first (e.g. LUMAJANG
        // should match before PSDA when both appear in the username).
        $tokens = array_keys($TOKEN_MAP);
        usort($tokens, function($a, $b) { return strlen($b) - strlen($a); });
        foreach ($tokens as $tk) {
            $code = $TOKEN_MAP[$tk];
            if ($u === $tk || $n === $tk || strpos($u, $tk) !== false || ($n && strpos($n, $tk) !== false)) {
                return $code;
            }
        }
        return null;
    }
}

if (!function_exists('print_bidang_options')) {
    function print_bidang_options(?string $locked = null) {
        // Use a list of pairs so we can emit multiple options with the same
        // nilai (kode bidang) but different labels (for UPT / Korwil).
        $options = [
            ['value' => '104.1',    'label' => 'Sekretariat'],
            ['value' => '104.2',    'label' => 'PSDA'],
            ['value' => '104.3',    'label' => 'SWP'],
            ['value' => '104.4',    'label' => 'Irigasi'],
            ['value' => '104.5',    'label' => 'Binfat'],

            ['value' => '104.6.02', 'label' => 'UPT Kediri'],

            ['value' => '104.6.05', 'label' => 'UPT Bojonegoro'],
            ['value' => '104.6.06', 'label' => 'UPT Bondowoso'],
            ['value' => '104.6.07', 'label' => 'UPT Lumajang'],
            ['value' => '104.6.08', 'label' => 'UPT Pasuruan'],
            ['value' => '104.6.09', 'label' => 'UPT Madura'],
        ];
        foreach ($options as $opt) {
            $val = $opt['value'];
            $label = $opt['label'];
            $sel = ($locked !== null && $locked === $val) ? ' selected' : '';
            echo '<option value="'.$val.'"'.$sel.'>'.$label.'</option>';
        }
    }
}

if (!function_exists('get_allowed_bidang_codes')) {
    function get_allowed_bidang_codes(): array {
        // Keep in sync with options in print_bidang_options(). Return unique values.
        return [
            '104.1','104.2','104.3','104.4','104.5',
            '104.6.02','104.6.05','104.6.06','104.6.07','104.6.08','104.6.09'
        ];
    }
}

if (!function_exists('resolve_bidang_code_from_label')) {
    // Map label tokens (as used in Nota Dinas form) to bidang codes.
    function resolve_bidang_code_from_label(string $label): ?string {
        $map = [
            'SEKRETARIAT' => '104.1',
            'PSDA'        => '104.2',
            'SWP'         => '104.3',
            'IRIGASI'     => '104.4',
            'BINFAT'      => '104.5',
        ];
        $key = strtoupper(trim($label));
        return $map[$key] ?? null;
    }
}

if (!function_exists('print_bidang_selector')) {
        function print_bidang_selector(?string $locked = null, string $name = 'bidang') {
            // If not locked, render a normal select (name required by forms)
            if ($locked === null) {
                echo '<select name="'.htmlspecialchars($name).'" id="'.htmlspecialchars($name).'" required>';
                print_bidang_options(null);
                echo '</select>';
                return;
            }

            // Locked: show a label + "Ubah" button that reveals a select. The
            // actual value submitted is stored in a hidden input named $name.
            $default = htmlspecialchars($locked, ENT_QUOTES);
            $json_default = json_encode($locked);
            $id = htmlspecialchars($name);

            echo '<input type="hidden" name="'.htmlspecialchars($name).'" id="'.$id.'-hidden" value="'.$default.'" />';
            echo '<div id="'.$id.'-display" style="display:flex;align-items:center;gap:8px;">';
            echo '<span class="grey-text" id="'.$id.'-label">Bidang otomatis: '.$default.'</span>';
            echo '<button type="button" id="'.$id.'-ubah" class="btn-small blue">Ubah</button>';
            echo '</div>';
            echo '<div id="'.$id.'-select-wrapper" style="display:none;margin-top:8px;">';
            echo '<select id="'.$id.'-select">';
            print_bidang_options($locked);
            echo '</select> ';
            echo '<button type="button" id="'.$id.'-batal" class="btn-small red">Batal</button>';
            echo '</div>';

            // Inline JS: toggle the select & keep hidden input in sync with choice. Also ensure value is set on form submit.
            echo '<script>(function(){var def=' . $json_default . '; var ub=document.getElementById("'.$id.'-ubah"); var wrap=document.getElementById("'.$id.'-select-wrapper"); var select=document.getElementById("'.$id.'-select"); var hidden=document.getElementById("'.$id.'-hidden"); var label=document.getElementById("'.$id.'-label"); var bat=document.getElementById("'.$id.'-batal"); if(!ub) return; ub.addEventListener("click",function(){wrap.style.display="block"; ub.style.display="none"; select.value=hidden.value; select.focus();}); select.addEventListener("change",function(){hidden.value=this.value; label.textContent="Bidang: "+this.value;}); bat.addEventListener("click",function(){wrap.style.display="none"; ub.style.display="inline-block"; hidden.value=def; label.textContent="Bidang otomatis: "+def;});
                // If the selector sits inside a form, make sure the hidden input is synchronized immediately before submit
                var el = hidden; while(el && el.tagName && el.tagName.toLowerCase() !== "form") { el = el.parentElement; }
                if (el && el.tagName && el.tagName.toLowerCase()==="form") {
                    el.addEventListener("submit", function(){ if(select && select.value) hidden.value = select.value; });
                }
            })();</script>';
        }
    }

?>
