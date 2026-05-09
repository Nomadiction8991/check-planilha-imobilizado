(() => {
    const patterns = {
        cpf: {
            inputMode: 'numeric',
            maxDigits: 11,
            format(value) {
                const digits = onlyDigits(value).slice(0, 11);

                if (digits.length <= 3) {
                    return digits;
                }

                if (digits.length <= 6) {
                    return `${digits.slice(0, 3)}.${digits.slice(3)}`;
                }

                if (digits.length <= 9) {
                    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
                }

                return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
            },
        },
        cnpj: {
            inputMode: 'numeric',
            maxDigits: 14,
            format(value) {
                const digits = onlyDigits(value).slice(0, 14);

                if (digits.length <= 2) {
                    return digits;
                }

                if (digits.length <= 5) {
                    return `${digits.slice(0, 2)}.${digits.slice(2)}`;
                }

                if (digits.length <= 8) {
                    return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
                }

                if (digits.length <= 12) {
                    return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
                }

                return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
            },
        },
        cep: {
            inputMode: 'numeric',
            maxDigits: 8,
            format(value) {
                const digits = onlyDigits(value).slice(0, 8);

                if (digits.length <= 5) {
                    return digits;
                }

                return `${digits.slice(0, 5)}-${digits.slice(5)}`;
            },
        },
        phone: {
            inputMode: 'numeric',
            maxDigits: 11,
            format(value) {
                const digits = onlyDigits(value).slice(0, 11);

                if (digits.length <= 2) {
                    return digits;
                }

                const areaCode = digits.slice(0, 2);
                const number = digits.slice(2);

                if (digits.length <= 6) {
                    return `(${areaCode}) ${number}`;
                }

                if (digits.length <= 10) {
                    return `(${areaCode}) ${number.slice(0, 4)}-${number.slice(4)}`;
                }

                return `(${areaCode}) ${number.slice(0, 5)}-${number.slice(5)}`;
            },
        },
    };

    const boundInputs = new WeakSet();

    function onlyDigits(value) {
        return String(value ?? '').replace(/\D+/g, '');
    }

    function caretPositionFromDigits(value, digitsBeforeCaret) {
        if (digitsBeforeCaret <= 0) {
            return 0;
        }

        let digitsSeen = 0;

        for (let index = 0; index < value.length; index += 1) {
            if (/\d/.test(value[index])) {
                digitsSeen += 1;

                if (digitsSeen >= digitsBeforeCaret) {
                    return index + 1;
                }
            }
        }

        return value.length;
    }

    function applyMask(input) {
        const maskName = String(input.dataset.mask || '').trim();
        const pattern = patterns[maskName];

        if (!pattern) {
            return;
        }

        const currentValue = input.value;
        const caretStart = typeof input.selectionStart === 'number' ? input.selectionStart : currentValue.length;
        const digitsBeforeCaret = onlyDigits(currentValue.slice(0, caretStart)).length;
        const formattedValue = pattern.format(currentValue);

        if (formattedValue !== currentValue) {
            input.value = formattedValue;
        }

        if (typeof input.setSelectionRange === 'function' && document.activeElement === input) {
            const nextCaret = caretPositionFromDigits(formattedValue, digitsBeforeCaret);
            input.setSelectionRange(nextCaret, nextCaret);
        }
    }

    function bindInput(input) {
        if (boundInputs.has(input)) {
            return;
        }

        const maskName = String(input.dataset.mask || '').trim();
        const pattern = patterns[maskName];

        if (!pattern) {
            return;
        }

        boundInputs.add(input);

        if (!input.hasAttribute('inputmode') && pattern.inputMode) {
            input.setAttribute('inputmode', pattern.inputMode);
        }

        applyMask(input);

        input.addEventListener('input', () => {
            applyMask(input);
        });

        input.addEventListener('blur', () => {
            applyMask(input);
        });
    }

    function refresh(root = document) {
        root.querySelectorAll('[data-mask]').forEach((input) => {
            if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                bindInput(input);
            }
        });
    }

    window.LegacyInputMask = {
        refresh,
        bindInput,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            refresh();
        });
    } else {
        refresh();
    }
})();
