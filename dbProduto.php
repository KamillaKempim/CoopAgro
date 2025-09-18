<?php
spl_autoload_register(function ($class) {
    require_once "Classes/{$class}.class.php";
});
//Criando uma instância da classe Produto
$Produto = new Produto();
if (filter_has_var(INPUT_POST, 'btnGravar')):

    $Produto->setNome(filter_input(INPUT_POST, "nome_produto", FILTER_SANITIZE_STRING));
    $Produto->setDesc(filter_input(INPUT_POST, "descricao", FILTER_SANITIZE_STRING));
    $Produto->setPreco(filter_input(INPUT_POST, "preco", FILTER_SANITIZE_STRING));
    $Produto->setUnidMed(filter_input(INPUT_POST, "unidade_medida", FILTER_SANITIZE_STRING));
    $id_produto = filter_input(INPUT_POST, 'id_produto');

    if (empty($id_produto)):
        //Tentar adicionar exibir mensagem ao usuário
        if ($Produto->add()) {
            echo "<script>window.alert('Produto inserido com sucesso!');window.location.href='apaProdutos.php';</script>";
        } else {
            echo "<script>window.alert('Erro ao inserir Produto!');window.open(document.referrer,'_self');</script>";
        }
    else:
        if ($Produto->update('id_produto', $id_produto)) {
            echo "<script> window.alert('Produto alterado com sucesso.');window.location.href='apaProdutos.php'; </script>";
        } else {
            echo "<script> window.alert('Erro ao alterar o produto.');window.open(document.referrer, '_self'); </script>";
        }

    endif;

elseif (filter_has_var(INPUT_POST, "btnDeletar")):
    $id_produto = intval(filter_input(INPUT_POST, "id_produto"));
    if ($Produto->delete("id_produto", $id_produto)) {
        header("location:apaProdutos.php");
    } else {
        echo "<script>window.alert('Erro ao Excluir');window(document.referrer,'_self');</script>";
    }
endif;