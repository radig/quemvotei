raiz, mostra informação sobre a aplicação (link para autenticar/criar conta)
GET /

# autentica o usuário usando OAUTH, cria uma conta
GET /:autentica:/

# lista os candidatos da eleição corrente/última
GET /:token:/meus-candidatos/

# seleciona candito ($ID é da candidatura, não candidato) para lista
POST /:token:/meus-candidatos/:ID:/
GET /:token:/meus-candidatos/adicionar/:ID:/

# remove candidato ($ID é da candidatura, não candidato) da lista
DELETE /:token:/meus-candidatos/:ID:/
GET /:token:/meus-candidatos/remover/:ID:/

# lista candidatos do $tipo na eleição $ano para o estado $uf e $cidade
GET /:token:/candidatos/:uf:/:cidade:/:tipo:/:ano:/

# visualiza candidato $ID
GET /:token:/candidato/:ID:/