<?php
include('mmzx_lib.php');

echo "+==============================+" . PHP_EOL;
echo "+ [NDS] Megaman ZX (U)         +" . PHP_EOL;
echo "+ Script Inserter v0.1         +" . PHP_EOL;
echo "+ Trans-Center, 2015           +" . PHP_EOL;
echo "+ Solid_One                    +" . PHP_EOL;
echo "+==============================+" . PHP_EOL;
echo PHP_EOL;
echo "Cách sử dụng:" . PHP_EOL;
echo " - Giải nén các file script từ rom Mỹ bằng Script Dumper, nếu bạn chưa thực hiện;" . PHP_EOL;
echo " - Bên trong thư mục 'scripts', tạo hai thư mục khác là 'trans' và 'reinsert';" . PHP_EOL;
echo " - Sao chép tất cả các file txt từ thư mục 'dumps' sang thư mục 'trans';" . PHP_EOL;
echo " - Bắt đầu dịch các tập lệnh trong thư mục 'trans', giữ nguyên các bản gốc trong thư mục 'dumps';" . PHP_EOL;
echo " - Khi bạn dịch hoặc khi mọi thứ kết thúc, hãy chạy tập lệnh này;" . PHP_EOL;
echo " - Sau đó, quá trình chèn lại sẽ bắt đầu, tạo lại các tệp .bin trong thư mục 'dumps', sẵn sàng để đưa lại vào trò chơi;" . PHP_EOL;
echo PHP_EOL;

aviso('Kiểm tra số lượng file dumps...', false);
$textos = glob('scripts/trans/*.txt', GLOB_BRACE);
$total_textos = count($textos);
aviso($total_textos);
if($total_textos > 0){
	aviso('Đang đọc table...', false);
	$tabela = lerTabelaCaracteres(true);
	aviso('OK!');

	aviso('Bắt đầu đóng gói lại file script...');
	foreach($textos as $texto) {
		$nome_arquivo_dumpado = basename($texto);
		if($nome_arquivo_dumpado == 'talk_q07_en1.txt'){
			aviso("Script \"$nome_arquivo_original\" là file null và sẽ bỏ qua.");
			continue;
		}
		$nome_arquivo_recompilado = str_replace('.txt', '.bin', $nome_arquivo_dumpado);
		
		aviso("Đóng gói file script \"$nome_arquivo_dumpado\" sang file \"$nome_arquivo_recompilado\"...", false);
		$texto = fopen($texto, 'r');
		$script = fopen("scripts/reinsert/$nome_arquivo_recompilado", 'w');
		
		$posicao_inicio_ponteiros = $posicao_fim_ponteiros = $posicao_inicio_textos = $posicao_inicio_script = '';
		
		$array_ponteiros = array();
		
		// Lendo informações do script
		$i=0;
		while (($linha = fgets($texto)) !== false) {
			if($i == 0){ // Cabeçalho do script
				$cabecalho = new SimpleXMLElement($linha);
				foreach($cabecalho->attributes() as $atributo => $valor) {
					if($atributo == 'inicio_ponteiros'){
						$posicao_inicio_ponteiros = (int)("$valor");
					} elseif($atributo == 'fim_ponteiros'){
						$posicao_fim_ponteiros = (int)("$valor");
					} elseif($atributo == 'inicio_textos'){
						$posicao_inicio_textos = (int)("$valor");
					}
				}
				$posicao_inicio_script = obterPosicaoInicioScript($posicao_inicio_textos);
				
				// Indo até a posição de início dos textos
				fseek($script, $posicao_inicio_textos);
			} elseif($i > 2){ // Texto do script
				//var_dump( ftell($texto) ); // PARA CASO SEJA PRECISO REFAZER A ITERAÇÂO DE CARACTERES DO SCRIPT
				$linha = utf8_decode($linha);
				if(trim($linha) == '<--------------------->'){ // Quebra de seção
					voltar1Byte($script);
					escreverByte($script, 'FD');
				} elseif(trim($linha) == '<*********************>'){ // Fim de texto
					voltar1Byte($script);
					escreverByte($script, 'FE');
					$array_ponteiros = adicionarPonteiro($array_ponteiros, $script, $posicao_inicio_textos);
				} else {
					$flag_tag = false;
					$flag_quebra_linha = false;
					$array_caracteres = str_split($linha);
					foreach($array_caracteres as $caractere){
						if($caractere == '<'){ // Lendo caracteres da tag
							$flag_tag = true;
							$caracteres_tag = '';
						} elseif($caractere == '>'){ // Leitura de caracteres da tag terminada
							$flag_tag = false;
							$tag = explode(' ', $caracteres_tag);
							
							// Obtenção de atributos e valores da tag, se existir
							$atributo_tag = $tag[0];
							if(isset($tag[1])){
								$valor_tag = $tag[1];
							} else {
								$valor_tag = '';
							}
							// Interpretação das tags
							if($atributo_tag == 'p'){ // Posição
								escreverByte($script, "F2{$valor_tag}");
							} elseif($atributo_tag == 'a'){ // Avatar
								escreverByte($script, "F3{$valor_tag}");
							} elseif($atributo_tag == 's'){ // Sexo do personagem
								escreverByte($script, "F4{$valor_tag}");
							} elseif($atributo_tag == 'e'){ // Escolha
								escreverByte($script, "F6{$valor_tag}");
							} elseif($atributo_tag == 'n'){ // Nome
								escreverByte($script, "F8{$valor_tag}");
							} elseif($atributo_tag == 'd'){ // Número do diálogo
								escreverByte($script, "F9{$valor_tag}");
							} elseif($atributo_tag == 'm'){ // Menu
								escreverByte($script, "FB");
							} elseif($atributo_tag == 'FIM'){ // Fim do Script
								escreverByte($script, "FF");
							} elseif($atributo_tag == 'dpad'){ // Direcional do controle
								escreverByte($script, "E0E1");
							} elseif($atributo_tag == 'btn_a'){ // Botão A
								escreverByte($script, "E2E3");
							} elseif($atributo_tag == 'btn_b'){ // Botão B
								escreverByte($script, "E4E5");
							} elseif($atributo_tag == 'cima'){ // Seta para cima
								escreverByte($script, "EE");
							} elseif($atributo_tag == 'baixo'){ // Seta para baixo
								escreverByte($script, "EF");
							} elseif($atributo_tag == 'esquerda'){ // Seta para esquerda
								escreverByte($script, "F000");
							} elseif($atributo_tag == 'direita'){ // Seta para direita
								escreverByte($script, "F001");
							} elseif($atributo_tag == 'cursor_esq'){ // Cursor para esquerda (menus de escolha)
								escreverByte($script, "F002");
								$flag_quebra_linha = true; // Workaround para script m_sys_en.txt, onde há o texto <cursor_esq>
							} elseif($atributo_tag == 'c'){ // Cor do texto
								escreverByte($script, "F1{$valor_tag}");
							} elseif($atributo_tag == 'nome'){ // Nome do protagonista
								escreverByte($script, "FA");
							} else {
								// Tag desconhecida, inserir valores hex diretamente
								if(!empty($atributo_tag)){
									escreverByte($script, $atributo_tag);
								}
							}
						} else {
							if($flag_tag){
								$caracteres_tag .= $caractere;
							} else {
								if(checkAlfanumerico($caractere) || checkSinalPontuacao($caractere) || $caractere == ' '){
									$flag_quebra_linha = true;
								}
								if($caractere == PHP_EOL){
									if($flag_quebra_linha === true){
										$flag_quebra_linha = false;
										escreverByte($script, "FC");
									}
								} else {
									// Parsear caractere em função da tabela
									$caractere_hex = converterCharByte($caractere, $tabela);
									escreverByte($script, $caractere_hex);
								}
							}
						}
					}
				}
			}
			$i++;
		}
		
		// Obtendo tamanho do arquivo
		$tamanho_arquivo = obterTamanhoArquivo($script);
		
		// Voltando ao começo do script, para atualizar tabela de ponteiros e outras informações
		voltarComeco($script);
		
		// Escrevendo informações de cabeçalho no script
		escreverByte($script, $tamanho_arquivo);
		escreverByte($script, $posicao_inicio_script);
		
		// Atualizar tabela de ponteiros
		array_unshift($array_ponteiros, '0000'); // Primeiro ponteiro sempre começa com 0000
		array_pop($array_ponteiros); // Último ponteiro é desnecessário na tabela
		foreach($array_ponteiros as $ponteiro){
			escreverByte($script, $ponteiro);
		}
		
		// Fechando arquivos abertos
		fclose($texto);
		fclose($script);
		aviso("OK!");
	}
	aviso('Đã đóng gói lại thành công file script!');
} else {
	aviso('Không tìm thấy file script nào!');
}
?>
