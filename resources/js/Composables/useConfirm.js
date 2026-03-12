import { reactive } from 'vue';

const confirmState = reactive({
  isOpen: false,
  title: '',
  message: '',
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
  tone: 'danger',
  _resolver: null,
});

function resetState() {
  confirmState.isOpen = false;
  confirmState.title = '';
  confirmState.message = '';
  confirmState.confirmText = 'Confirmar';
  confirmState.cancelText = 'Cancelar';
  confirmState.tone = 'danger';
  confirmState._resolver = null;
}

export function useConfirm() {
  const ask = ({
    title = 'Confirmar accion',
    message = 'Esta accion no se puede deshacer.',
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    tone = 'danger',
  } = {}) => {
    if (confirmState._resolver) {
      confirmState._resolver(false);
    }

    confirmState.isOpen = true;
    confirmState.title = title;
    confirmState.message = message;
    confirmState.confirmText = confirmText;
    confirmState.cancelText = cancelText;
    confirmState.tone = tone;

    return new Promise((resolve) => {
      confirmState._resolver = resolve;
    });
  };

  const confirm = () => {
    if (confirmState._resolver) {
      confirmState._resolver(true);
    }
    resetState();
  };

  const cancel = () => {
    if (confirmState._resolver) {
      confirmState._resolver(false);
    }
    resetState();
  };

  return {
    confirmState,
    ask,
    confirm,
    cancel,
  };
}
