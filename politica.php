<?php
// Inicia a sessão no início do arquivo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso e Política de Privacidade - CoopAgro</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/politica.css" />
</head>
<body>
    <!-- VLibras -->
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper></div>
    </div>

    <?php include_once 'includes/_acessibilidade.php'; ?>
    
    <!-- Menu de Navegação -->
    <?php include 'includes/_menu.php'; ?>

    <main class="politica-container container mt-4 mb-5">
        <!-- Cabeçalho -->
        <div class="row align-items-center mb-5">
            <div class="col-md-8">
                <div class="politica-header-content p-4 rounded-3" style="background: linear-gradient(135deg, #143d0f 0%, #2e7d32 100%); color: white;">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-file-contract me-2"></i>Termos de Uso e Política de Privacidade
                    </h1>
                    <p class="lead mb-3">CoopAgro - Cooperativa Agropecuária</p>
                    <div class="d-flex flex-wrap gap-4">
                        <span><i class="fas fa-calendar-alt me-2"></i>Última atualização: 01/10/2025</span>
                        <span><i class="fas fa-shield-alt me-2"></i>Conforme LGPD (Lei nº 13.709/2018)</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="image-placeholder p-4 rounded-3" style="background-color: #f8f9fa; border: 2px solid #e9ecef;">
                    <i class="fas fa-seedling display-1 text-success mb-3"></i>
                    <h5 class="fw-bold">Transparência e Segurança</h5>
                </div>
            </div>
        </div>

        <!-- Navegação Rápida -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-light">
                <h3 class="mb-0"><i class="fas fa-bookmark me-2 text-success"></i>Navegação Rápida</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $navItems = [
                        ['icon' => 'info-circle', 'color' => 'primary', 'text' => 'Introdução', 'link' => '#introducao'],
                        ['icon' => 'database', 'color' => 'info', 'text' => 'Dados Coletados', 'link' => '#dados-coletados'],
                        ['icon' => 'bullseye', 'color' => 'success', 'text' => 'Finalidade', 'link' => '#finalidade'],
                        ['icon' => 'balance-scale', 'color' => 'warning', 'text' => 'Base Legal', 'link' => '#base-legal'],
                        ['icon' => 'share-alt', 'color' => 'secondary', 'text' => 'Compartilhamento', 'link' => '#compartilhamento'],
                        ['icon' => 'lock', 'color' => 'dark', 'text' => 'Armazenamento', 'link' => '#armazenamento'],
                        ['icon' => 'user-shield', 'color' => 'primary', 'text' => 'Direitos', 'link' => '#direitos'],
                        ['icon' => 'user-check', 'color' => 'info', 'text' => 'Responsabilidades', 'link' => '#responsabilidades'],
                        ['icon' => 'sync-alt', 'color' => 'success', 'text' => 'Alterações', 'link' => '#alteracoes'],
                        ['icon' => 'address-card', 'color' => 'warning', 'text' => 'Contato', 'link' => '#contato'],
                        ['icon' => 'gavel', 'color' => 'danger', 'text' => 'Foro', 'link' => '#foro']
                    ];
                    
                    foreach ($navItems as $item):
                    ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="<?php echo $item['link']; ?>" 
                           class="nav-card d-block text-decoration-none p-3 rounded-2 text-center border"
                           style="border-color: #e9ecef !important;">
                            <i class="fas fa-<?php echo $item['icon']; ?> fs-4 text-<?php echo $item['color']; ?> mb-2"></i>
                            <span class="d-block fw-medium"><?php echo $item['text']; ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="politica-content">
            <!-- Seção 1: Introdução -->
            <section id="introducao" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-success me-2">1</span>Introdução</h2>
                    <div class="section-icon"><i class="fas fa-info-circle fs-4 text-success"></i></div>
                </div>
                <div class="card-body">
                    <p>A <strong class="text-success">CoopAgro - Cooperativa Agropecuária</strong> valoriza a transparência, a segurança e a privacidade de seus associados, parceiros, agricultores e comerciantes. Este documento tem como objetivo esclarecer como realizamos a coleta, o tratamento, o armazenamento e a utilização dos dados pessoais fornecidos pelos usuários em nosso site, em conformidade com a <strong class="text-success">Lei Geral de Proteção de Dados Pessoais - LGPD (Lei nº 13.709/2018)</strong>.</p>
                    <div class="alert alert-warning mt-3">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-circle fs-4 me-3"></i>
                            <div>
                                <p class="mb-0"><strong>Atenção:</strong> Ao utilizar o site da CoopAgro, o usuário declara estar ciente e de acordo com os termos aqui estabelecidos. Caso não concorde, recomendamos que não prossiga com a utilização do site.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Seção 2: Dados Coletados -->
            <section id="dados-coletados" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-info me-2">2</span>Dados Pessoais Coletados</h2>
                    <div class="section-icon"><i class="fas fa-database fs-4 text-info"></i></div>
                </div>
                <div class="card-body">
                    <p>Para a adequada prestação de nossos serviços e atendimento às finalidades da CoopAgro, poderemos coletar e tratar os seguintes dados pessoais:</p>
                    <div class="row g-3 mt-3">
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="data-item text-center p-3 border rounded-2">
                                <i class="fas fa-user fs-4 text-primary mb-2"></i>
                                <div class="fw-medium">Nome completo</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="data-item text-center p-3 border rounded-2">
                                <i class="fas fa-id-card fs-4 text-warning mb-2"></i>
                                <div class="fw-medium">CPF</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="data-item text-center p-3 border rounded-2">
                                <i class="fas fa-phone fs-4 text-success mb-2"></i>
                                <div class="fw-medium">Telefone</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="data-item text-center p-3 border rounded-2">
                                <i class="fas fa-envelope fs-4 text-danger mb-2"></i>
                                <div class="fw-medium">E-mail</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-8 col-lg-6 mx-auto">
                            <div class="data-item text-center p-3 border rounded-2">
                                <i class="fas fa-map-marker-alt fs-4 text-info mb-2"></i>
                                <div class="fw-medium">Endereço completo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Seção 3: Finalidade -->
            <section id="finalidade" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-success me-2">3</span>Finalidade do Tratamento dos Dados</h2>
                    <div class="section-icon"><i class="fas fa-bullseye fs-4 text-success"></i></div>
                </div>
                <div class="card-body">
                    <p>Os dados pessoais coletados têm como finalidades principais:</p>
                    <div class="list-group list-group-flush mt-3">
                        <?php
                        $finalidades = [
                            'Identificação e autenticação dos associados e parceiros',
                            'Possibilitar comunicação entre a CoopAgro e seus cooperados/comerciantes',
                            'Viabilizar o cadastro de novos membros, agricultores e comerciantes',
                            'Envio de informações relevantes sobre atividades, eventos e serviços',
                            'Entrega dos produtos aos destinatários solicitantes',
                            'Cumprimento de obrigações legais e regulatórias aplicáveis',
                            'Análise de atividades agrícolas para oferecimento de crédito personalizado',
                            'Aprimoramento da experiência do usuário no site'
                        ];
                        
                        foreach ($finalidades as $finalidade):
                        ?>
                        <div class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-3"></i>
                            <span><?php echo $finalidade; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Seção 4: Base Legal -->
            <section id="base-legal" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-warning me-2">4</span>Base Legal para o Tratamento</h2>
                    <div class="section-icon"><i class="fas fa-balance-scale fs-4 text-warning"></i></div>
                </div>
                <div class="card-body">
                    <p>O tratamento dos dados pessoais pela CoopAgro será realizado com base nas seguintes hipóteses legais previstas na LGPD:</p>
                    <div class="row g-4 mt-3">
                        <?php
                        $basesLegais = [
                            ['icon' => 'handshake', 'color' => 'primary', 'title' => 'Consentimento do titular', 'desc' => 'Art. 7º, I da LGPD'],
                            ['icon' => 'file-contract', 'color' => 'success', 'title' => 'Execução de contrato', 'desc' => 'Art. 7º, V da LGPD'],
                            ['icon' => 'gavel', 'color' => 'danger', 'title' => 'Cumprimento de obrigação legal', 'desc' => 'Art. 7º, II da LGPD'],
                            ['icon' => 'briefcase', 'color' => 'info', 'title' => 'Legítimo interesse', 'desc' => 'Art. 7º, IX da LGPD']
                        ];
                        
                        foreach ($basesLegais as $base):
                        ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="legal-card text-center p-3 border rounded-2 h-100">
                                <div class="legal-icon mb-3">
                                    <i class="fas fa-<?php echo $base['icon']; ?> fs-1 text-<?php echo $base['color']; ?>"></i>
                                </div>
                                <h5 class="fw-bold"><?php echo $base['title']; ?></h5>
                                <p class="text-muted"><?php echo $base['desc']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Seção 5: Compartilhamento -->
            <section id="compartilhamento" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-secondary me-2">5</span>Compartilhamento de Dados</h2>
                    <div class="section-icon"><i class="fas fa-share-alt fs-4 text-secondary"></i></div>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <div class="d-flex">
                            <i class="fas fa-ban fs-4 me-3"></i>
                            <div>
                                <h5 class="alert-heading">A CoopAgro <strong>NÃO</strong> comercializa dados pessoais</h5>
                                <p class="mb-2">O compartilhamento poderá ocorrer apenas nos seguintes casos:</p>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <?php
                        $compartilhamentos = [
                            ['icon' => 'landmark', 'color' => 'warning', 'title' => 'Órgãos governamentais', 'desc' => 'Quando exigido por lei'],
                            ['icon' => 'handshake', 'color' => 'success', 'title' => 'Prestadores de serviços', 'desc' => 'Sob cláusulas de confidencialidade'],
                            ['icon' => 'shield-alt', 'color' => 'primary', 'title' => 'Proteção de interesses', 'desc' => 'Em processos judiciais ou administrativos']
                        ];
                        
                        foreach ($compartilhamentos as $comp):
                        ?>
                        <div class="col-md-4">
                            <div class="sharing-item d-flex align-items-center p-3 border rounded-2">
                                <div class="sharing-icon me-3">
                                    <i class="fas fa-<?php echo $comp['icon']; ?> fs-3 text-<?php echo $comp['color']; ?>"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo $comp['title']; ?></h6>
                                    <p class="text-muted mb-0"><?php echo $comp['desc']; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Seção 6: Armazenamento -->
            <section id="armazenamento" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-dark me-2">6</span>Armazenamento e Segurança</h2>
                    <div class="section-icon"><i class="fas fa-lock fs-4 text-dark"></i></div>
                </div>
                <div class="card-body">
                    <p>A CoopAgro adota medidas técnicas e organizacionais aptas a proteger os dados pessoais contra acessos não autorizados, perda, alteração ou qualquer forma de tratamento inadequado ou ilícito, incluindo, mas não se limitando a:</p>
                    <div class="row g-3 mt-3">
                        <?php
                        $seguranca = [
                            ['icon' => 'user-lock', 'color' => 'primary', 'title' => 'Controle de Acesso', 'desc' => 'Acesso restrito a colaboradores autorizados'],
                            ['icon' => 'key', 'color' => 'warning', 'title' => 'Criptografia', 'desc' => 'Utilização de ferramentas de segurança da informação'],
                            ['icon' => 'shield-alt', 'color' => 'success', 'title' => 'Pilares de Segurança', 'desc' => 'Confidencialidade, integridade e disponibilidade'],
                            ['icon' => 'hdd', 'color' => 'info', 'title' => 'Backup Regular', 'desc' => 'Redundância e criptografia conforme ISO 27002'],
                            ['icon' => 'chart-line', 'color' => 'danger', 'title' => 'Monitoramento', 'desc' => 'Auditoria periódica dos sistemas utilizados']
                        ];
                        
                        foreach ($seguranca as $seg):
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="security-card text-center p-3 border rounded-2 h-100">
                                <i class="fas fa-<?php echo $seg['icon']; ?> fs-3 text-<?php echo $seg['color']; ?> mb-3"></i>
                                <h6 class="fw-bold"><?php echo $seg['title']; ?></h6>
                                <p class="text-muted small"><?php echo $seg['desc']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Período de armazenamento:</strong> Os dados serão armazenados pelo período necessário para cumprimento das finalidades legais, regulatórias e contratuais, ou até que o titular solicite a eliminação, salvo hipóteses em que a manutenção seja exigida por lei.
                    </div>
                </div>
            </section>

            <!-- Seção 7: Direitos -->
            <section id="direitos" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-primary me-2">7</span>Direitos dos Titulares</h2>
                    <div class="section-icon"><i class="fas fa-user-shield fs-4 text-primary"></i></div>
                </div>
                <div class="card-body">
                    <p>Nos termos da LGPD, o titular dos dados pessoais poderá, a qualquer tempo, solicitar à CoopAgro:</p>
                    <div class="row g-3 mt-3">
                        <?php
                        $direitos = [
                            ['icon' => 'search', 'color' => 'primary', 'text' => 'Confirmação da existência de tratamento'],
                            ['icon' => 'eye', 'color' => 'info', 'text' => 'Acesso aos seus dados pessoais'],
                            ['icon' => 'edit', 'color' => 'warning', 'text' => 'Correção de dados incompletos ou inexatos'],
                            ['icon' => 'user-secret', 'color' => 'dark', 'text' => 'Anonimização, bloqueio ou eliminação'],
                            ['icon' => 'exchange-alt', 'color' => 'success', 'text' => 'Portabilidade dos dados'],
                            ['icon' => 'undo', 'color' => 'danger', 'text' => 'Revogação do consentimento'],
                            ['icon' => 'trash-alt', 'color' => 'secondary', 'text' => 'Eliminação dos dados pessoais']
                        ];
                        
                        foreach ($direitos as $direito):
                        ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="right-item text-center p-3 border rounded-2">
                                <i class="fas fa-<?php echo $direito['icon']; ?> fs-4 text-<?php echo $direito['color']; ?> mb-2"></i>
                                <div class="fw-medium small"><?php echo $direito['text']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        As solicitações deverão ser encaminhadas ao <strong>Encarregado de Proteção de Dados (DPO)</strong> da CoopAgro, através dos canais de contato indicados neste documento.
                    </div>
                </div>
            </section>

            <!-- Seção 8: Responsabilidades -->
            <section id="responsabilidades" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-info me-2">8</span>Responsabilidades do Usuário</h2>
                    <div class="section-icon"><i class="fas fa-user-check fs-4 text-info"></i></div>
                </div>
                <div class="card-body">
                    <p>O usuário é responsável por:</p>
                    <div class="row g-3 mt-3">
                        <?php
                        $responsabilidades = [
                            ['icon' => 'check', 'color' => 'success', 'title' => 'Fornecer informações verdadeiras', 'desc' => 'Informações completas e atualizadas'],
                            ['icon' => 'key', 'color' => 'warning', 'title' => 'Manter a confidencialidade', 'desc' => 'Das credenciais de acesso'],
                            ['icon' => 'exclamation-triangle', 'color' => 'danger', 'title' => 'Não compartilhar credenciais', 'desc' => 'A CoopAgro nunca solicitará dados por e-mail ou telefone'],
                            ['icon' => 'file-signature', 'color' => 'primary', 'title' => 'Utilizar conforme os termos', 'desc' => 'Em conformidade com a legislação vigente']
                        ];
                        
                        foreach ($responsabilidades as $resp):
                        ?>
                        <div class="col-md-6">
                            <div class="responsibility-item d-flex p-3 border rounded-2 h-100">
                                <div class="responsibility-icon me-3">
                                    <i class="fas fa-<?php echo $resp['icon']; ?> fs-3 text-<?php echo $resp['color']; ?>"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold"><?php echo $resp['title']; ?></h6>
                                    <p class="text-muted small mb-0"><?php echo $resp['desc']; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Seção 9: Alterações -->
            <section id="alteracoes" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-success me-2">9</span>Alterações deste Documento</h2>
                    <div class="section-icon"><i class="fas fa-sync-alt fs-4 text-success"></i></div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="fas fa-bell fs-4 me-3"></i>
                            <div>
                                <p class="mb-0">A CoopAgro reserva-se o direito de alterar estes Termos de Uso e Política de Privacidade a qualquer tempo, mediante publicação da versão atualizada no site, com a devida comunicação aos usuários quando houver mudanças significativas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Seção 10: Contato -->
            <section id="contato" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-warning me-2">10</span>Contato e Encarregado de Dados (DPO)</h2>
                    <div class="section-icon"><i class="fas fa-address-card fs-4 text-warning"></i></div>
                </div>
                <div class="card-body">
                    <p>Para exercer seus direitos ou esclarecer dúvidas sobre este documento, o titular poderá entrar em contato com o Encarregado de Proteção de Dados da CoopAgro:</p>
                    <div class="card border-success mt-3">
                        <div class="card-header bg-success text-white">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-tie fs-4 me-3"></i>
                                <h4 class="mb-0">Encarregado de Proteção de Dados (DPO)</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            $contatos = [
                                ['icon' => 'building', 'title' => 'CoopAgro - Cooperativa Agropecuária', 'value' => ''],
                                ['icon' => 'envelope', 'title' => 'E-mail', 'value' => 'jhiewertton1@gmail.com'],
                                ['icon' => 'phone', 'title' => 'Telefone', 'value' => '69 99920-0370'],
                                ['icon' => 'map-marker-alt', 'title' => 'Endereço', 'value' => 'Beco 02, N° 24, Bairro BNH7']
                            ];
                            
                            foreach ($contatos as $contato):
                            ?>
                            <div class="row mb-3">
                                <div class="col-auto">
                                    <i class="fas fa-<?php echo $contato['icon']; ?> fs-4 text-success"></i>
                                </div>
                                <div class="col">
                                    <h6 class="fw-bold mb-1"><?php echo $contato['title']; ?></h6>
                                    <?php if (!empty($contato['value'])): ?>
                                    <p class="mb-0"><?php echo $contato['value']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($contato['icon'] != 'map-marker-alt'): ?>
                            <hr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Seção 11: Foro -->
            <section id="foro" class="politica-section card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><span class="badge bg-danger me-2">11</span>Foro Aplicável</h2>
                    <div class="section-icon"><i class="fas fa-gavel fs-4 text-danger"></i></div>
                </div>
                <div class="card-body">
                    <div class="alert alert-dark">
                        <div class="d-flex">
                            <i class="fas fa-balance-scale fs-4 me-3"></i>
                            <div>
                                <p class="mb-0">Este documento será regido pelas leis brasileiras, em especial a <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018)</strong>. Fica eleito o foro da comarca onde está sediada a CoopAgro para dirimir quaisquer questões oriundas destes termos, com renúncia a qualquer outro, por mais privilegiado que seja.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Botão Voltar ao Topo -->
            <div class="text-center mt-5 mb-4">
                <a href="#" id="backToTop" class="btn btn-success btn-lg">
                    <i class="fas fa-arrow-up me-2"></i>Voltar ao Topo
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/_footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- VLibras -->
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    
    <!-- Scripts Personalizados -->
    <script>
        // Inicializar VLibras
        new window.VLibras.Widget('https://vlibras.gov.br/app');
        
        // Scroll suave para âncoras
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Mostrar/ocultar botão "Voltar ao Topo"
        const backToTopBtn = document.getElementById('backToTop');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 500) {
                backToTopBtn.style.opacity = '1';
                backToTopBtn.style.visibility = 'visible';
                backToTopBtn.style.transform = 'translateY(0)';
            } else {
                backToTopBtn.style.opacity = '0';
                backToTopBtn.style.visibility = 'hidden';
                backToTopBtn.style.transform = 'translateY(20px)';
            }
        });
        
        // Configurar botão "Voltar ao Topo"
        backToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Inicializar tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Animação para seções quando são clicadas
        document.querySelectorAll('.politica-section').forEach(section => {
            section.addEventListener('click', function() {
                this.style.transform = 'scale(0.99)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>

    <style>
        /* Estilos adicionais para melhorar a aparência */
        .politica-section {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .politica-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        
        .nav-card {
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .nav-card:hover {
            transform: translateY(-3px);
            background-color: #e9ecef;
            border-color: #dee2e6 !important;
        }
        
        .data-item, .legal-card, .sharing-item, .security-card, .right-item, .responsibility-item {
            transition: all 0.3s ease;
        }
        
        .data-item:hover, .legal-card:hover, .sharing-item:hover, 
        .security-card:hover, .right-item:hover, .responsibility-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        #backToTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transform: translateY(20px);
        }
        
        #backToTop:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        
        /* Estilo para as seções ativas durante a navegação */
        :target {
            animation: highlight 1.5s ease;
        }
        
        @keyframes highlight {
            0% { background-color: transparent; }
            50% { background-color: rgba(20, 61, 15, 0.1); }
            100% { background-color: transparent; }
        }
        
        /* Melhorar a responsividade */
        @media (max-width: 768px) {
            .politica-header-content {
                text-align: center;
            }
            
            .image-placeholder {
                margin-top: 20px;
            }
            
            .nav-card {
                min-height: 80px;
                padding: 15px !important;
            }
            
            .nav-card span {
                font-size: 0.8rem;
            }
            
            .data-item, .legal-card, .right-item {
                padding: 15px !important;
            }
            
            #backToTop {
                bottom: 20px;
                right: 20px;
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .nav-card {
                min-height: 70px;
            }
            
            .nav-card i {
                font-size: 1.2rem !important;
                margin-bottom: 5px !important;
            }
            
            .nav-card span {
                font-size: 0.7rem;
            }
            
            .card-header h2 {
                font-size: 1.2rem;
            }
            
            .section-icon i {
                font-size: 1.5rem !important;
            }
        }
        
        /* Melhorar legibilidade */
        .card-body p {
            line-height: 1.8;
            text-align: justify;
        }
        
        .list-group-item {
            padding: 0.75rem 0;
        }
        
        /* Estilos para badges */
        .badge {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
</body>
</html>