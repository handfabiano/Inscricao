// Sistema de Inscrição de Atletas - JavaScript

// Aplicar máscaras nos campos
document.addEventListener('DOMContentLoaded', function() {
    // Máscara de CPF
    const cpfInputs = document.querySelectorAll('#cpf, #responsavel_cpf');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });

        input.addEventListener('blur', function(e) {
            if (e.target.id === 'cpf') {
                validateCPF(e.target);
            }
        });
    });

    // Máscara de Telefone
    const phoneInputs = document.querySelectorAll('#telefone, #celular, #responsavel_telefone');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            }
        });
    });

    // Máscara de CEP
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        cepInput.addEventListener('blur', function() {
            buscarCEP(this.value);
        });
    }

    // Verificar idade para mostrar/ocultar campos do responsável
    const dataNascimento = document.getElementById('data_nascimento');
    if (dataNascimento) {
        dataNascimento.addEventListener('change', function() {
            verificarIdade(this.value);
        });
    }

    // Validação do formulário antes de enviar
    const form = document.getElementById('inscricaoForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validarFormulario()) {
                e.preventDefault();
            }
        });
    }
});

// Validar CPF
function validateCPF(input) {
    const cpf = input.value.replace(/\D/g, '');
    const errorElement = document.getElementById('cpf-error');

    if (cpf.length === 0) {
        errorElement.textContent = '';
        input.classList.remove('error');
        return true;
    }

    if (!isValidCPF(cpf)) {
        errorElement.textContent = 'CPF inválido';
        input.classList.add('error');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.remove('error');
        return true;
    }
}

function isValidCPF(cpf) {
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return false;
    }

    let sum = 0;
    let remainder;

    for (let i = 1; i <= 9; i++) {
        sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }

    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.substring(9, 10))) return false;

    sum = 0;
    for (let i = 1; i <= 10; i++) {
        sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }

    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.substring(10, 11))) return false;

    return true;
}

// Buscar CEP via API ViaCEP
function buscarCEP(cep) {
    cep = cep.replace(/\D/g, '');

    if (cep.length !== 8) {
        return;
    }

    const enderecoInput = document.getElementById('endereco');
    const bairroInput = document.getElementById('bairro');
    const cidadeInput = document.getElementById('cidade');
    const estadoInput = document.getElementById('estado');

    // Mostrar loading
    enderecoInput.value = 'Buscando...';

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                enderecoInput.value = data.logradouro || '';
                bairroInput.value = data.bairro || '';
                cidadeInput.value = data.localidade || '';
                estadoInput.value = data.uf || '';
                document.getElementById('numero').focus();
            } else {
                enderecoInput.value = '';
                alert('CEP não encontrado!');
            }
        })
        .catch(error => {
            enderecoInput.value = '';
            console.error('Erro ao buscar CEP:', error);
        });
}

// Verificar idade e mostrar campos do responsável
function verificarIdade(dataNascimento) {
    if (!dataNascimento) return;

    const hoje = new Date();
    const nascimento = new Date(dataNascimento);
    let idade = hoje.getFullYear() - nascimento.getFullYear();
    const mes = hoje.getMonth() - nascimento.getMonth();

    if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
    }

    const responsavelSection = document.getElementById('responsavel-section');
    const responsavelInputs = responsavelSection.querySelectorAll('input, select');

    if (idade < 18) {
        responsavelSection.style.display = 'block';
        responsavelInputs.forEach(input => {
            if (input.id !== 'responsavel_parentesco') {
                input.setAttribute('required', 'required');
            }
        });
    } else {
        responsavelSection.style.display = 'none';
        responsavelInputs.forEach(input => {
            input.removeAttribute('required');
        });
    }
}

// Validar formulário completo
function validarFormulario() {
    let valido = true;
    const form = document.getElementById('inscricaoForm');

    // Validar CPF
    const cpfInput = document.getElementById('cpf');
    if (!validateCPF(cpfInput)) {
        valido = false;
    }

    // Validar data de nascimento
    const dataNascimento = document.getElementById('data_nascimento').value;
    if (dataNascimento) {
        const hoje = new Date();
        const nascimento = new Date(dataNascimento);

        if (nascimento > hoje) {
            alert('Data de nascimento não pode ser futura!');
            valido = false;
        }

        let idade = hoje.getFullYear() - nascimento.getFullYear();
        if (idade < 5 || idade > 100) {
            alert('Idade deve estar entre 5 e 100 anos!');
            valido = false;
        }
    }

    // Validar email
    const email = document.getElementById('email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Email inválido!');
        valido = false;
    }

    // Validar arquivos
    const fotoInput = document.getElementById('foto');
    const docIdentidadeInput = document.getElementById('documento_identidade');
    const compResidenciaInput = document.getElementById('comprovante_residencia');

    if (!validarArquivo(fotoInput, ['jpg', 'jpeg', 'png'])) {
        alert('Foto deve ser JPG ou PNG!');
        valido = false;
    }

    if (!validarArquivo(docIdentidadeInput, ['jpg', 'jpeg', 'png', 'pdf'])) {
        alert('Documento de identidade deve ser JPG, PNG ou PDF!');
        valido = false;
    }

    if (!validarArquivo(compResidenciaInput, ['jpg', 'jpeg', 'png', 'pdf'])) {
        alert('Comprovante de residência deve ser JPG, PNG ou PDF!');
        valido = false;
    }

    // Validar termos
    const termos = document.getElementById('aceite_termos');
    if (!termos.checked) {
        alert('Você deve aceitar os termos e condições!');
        valido = false;
    }

    return valido;
}

// Validar arquivo
function validarArquivo(input, extensoesPermitidas) {
    if (!input.files || input.files.length === 0) {
        return !input.hasAttribute('required');
    }

    const arquivo = input.files[0];
    const nomeArquivo = arquivo.name.toLowerCase();
    const extensao = nomeArquivo.split('.').pop();

    // Validar extensão
    if (!extensoesPermitidas.includes(extensao)) {
        return false;
    }

    // Validar tamanho (5MB)
    const tamanhoMaximo = 5 * 1024 * 1024;
    if (arquivo.size > tamanhoMaximo) {
        alert(`O arquivo ${arquivo.name} excede o tamanho máximo de 5MB!`);
        return false;
    }

    return true;
}

// Confirmar antes de limpar formulário
document.addEventListener('DOMContentLoaded', function() {
    const resetButton = document.querySelector('button[type="reset"]');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja limpar todo o formulário?')) {
                e.preventDefault();
            }
        });
    }
});

// Preview de imagens
document.addEventListener('DOMContentLoaded', function() {
    const imageInputs = document.querySelectorAll('input[type="file"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Criar preview se necessário
                    console.log('Arquivo selecionado:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    });
});
