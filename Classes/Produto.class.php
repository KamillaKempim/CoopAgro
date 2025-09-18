<?php
//Nome do Produto, Descrição, Preço, Unidade de Medida
class Produto extends CRUD
{
    protected $table = "produto";
    private $id_produto;
    private $nome_produto;
    private $descricao;
    private $preco;
    private $unidade_medida;

    public function setId($id_produto)
    {
        $this->id_produto = $id_produto;
    }
    public function setNome($nome_produto)
    {
        $this->nome_produto = $nome_produto;
    }
    public function setDesc($descricao)
    {
        $this->descricao = $descricao;
    }
    public function setPreco($preco)
    {
        $this->preco = $preco;
    }
    public function setUnidMed($unidade_medida)
    {
        $this->unidade_medida = $unidade_medida;
    }
    public function getId_produto()
    {
        return $this->id_produto;
    }
    public function getNome()
    {
        return $this->nome_produto;
    }
    public function getDesc()
    {
        return $this->descricao;
    }
    public function getPreco()
    {
        return $this->preco;
    }
    public function getUnidMed()
    {
        return $this->unidade_medida;
    }

    public function add()
    {
        $sql = "INSERT INTO $this->table (nome_produto, descricao, preco, unidade_medida) VALUES (:nome_produto, :descricao, :preco, :unidade_medida)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":nome_produto", $this->nome_produto, PDO::PARAM_STR);
        $stmt->bindParam(":descricao", $this->descricao, PDO::PARAM_STR);
        $stmt->bindParam(":preco", $this->preco, PDO::PARAM_STR);
        $stmt->bindParam(":unidade_medida", $this->unidade_medida, PDO::PARAM_STR);
        return $stmt->execute();
    }
    public function update(string $campo, int $id_produto)
    {
        $sql = "UPDATE $this->table SET nome_produto = :nome_produto, descricao = :descricao, preco = :preco, unidade_medida = :unidade_medida WHERE $campo = :id_produto";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":nome_produto", $this->nome_produto, PDO::PARAM_STR);
        $stmt->bindParam(":descricao", $this->descricao, PDO::PARAM_STR);
        $stmt->bindParam(":preco", $this->preco, PDO::PARAM_STR);
        $stmt->bindParam(":unidade_medida", $this->unidade_medida, PDO::PARAM_STR);
        $stmt->bindParam(":id_produto", $id_produto, PDO::PARAM_INT);

        return $stmt->execute();
    }
}