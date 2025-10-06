<?php

return [
    // Mapa de destinatarios por operación y etapa de aprobación
    // stage2: cuando pasa a estatus 2 (usa stage2_by_role para enviar según rol objetivo)
    // stage3: cuando pasa a estatus 3 (Director Contable / Gerente Financiero)
    // final: cuando pasa a estatus 4 (Aprobación final)
    'destinatarios_por_operacion' => [
        'default' => [
            'stage2' => [
                // Lista general si se hace envio de correos sin importar el rol
                // 'dir.proyectos@empresa.com', 'ger.operaciones@empresa.com', 'ger.talento@empresa.com', 'dir.contable@empresa.com', 'ger.finanzas@empresa.com'
            ],
            // Por rol objetivo en stage2 (claves normalizadas: minúsculas, sin acentos)
            'stage2_by_role' => [
                //'director de proyectos' => ['rewiket243@aiwanlab.com'],
                //'gerente operaciones'   => ['rewiket243@aiwanlab.com'],
                // 'gerente talento humano'=> ['ger.talento@empresa.com'],
                // 'director contable'     => ['dir.contable@empresa.com'],
            ],
            'stage3' => [
                // 'Gerente Financiero'
                //'jsqkkhc0y4@wyoxafp.com',
            ],
            'final'  => [
                // 'compras@empresa.com', 'areacompras@empresa.com'
            ],
        ],
    ],

    // Destinatarios por defecto para correo de OC creada 
    'destinatarios_oc' => [
        'to' => [
            //'rewiket243@aiwanlab.com'
        ],
        'cc' => [
            // Correos en copia
        ],
    ],
];
