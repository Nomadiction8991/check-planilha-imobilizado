<?php
// Este arquivo foi descontinuado.
// A lógica de exclusão em massa foi movida para ProdutoController::bulkDelete()
// Rota: POST /products/bulk-delete
header('HTTP/1.1 410 Gone');
echo json_encode(['success' => false, 'message' => 'Endpoint movido para /products/bulk-delete']);
