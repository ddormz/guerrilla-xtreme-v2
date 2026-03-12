<template>
  <div v-if="show" class="cropper-modal-overlay">
    <div class="cropper-modal card">
      <div class="cropper-header">
        <h3>Recortar Imagen de Perfil</h3>
        <button @click="close" class="btn-close">&times;</button>
      </div>
      
      <div class="cropper-body">
        <div class="cropper-wrapper">
          <Cropper
            ref="cropperRef"
            class="advanced-cropper"
            :src="imageSrc"
            :stencil-component="CircleStencil"
            :stencil-props="{
              aspectRatio: 1
            }"
            :canvas="{
              minHeight: 512,
              minWidth: 512,
              maxHeight: 512,
              maxWidth: 512
            }"
            image-restriction="stencil"
            @change="onChange"
          />
        </div>
      </div>

      <div class="cropper-footer">
        <button @click="close" class="btn btn-secondary">Cancelar</button>
        <button @click="crop" class="btn btn-primary">Recortar y Guardar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Cropper, CircleStencil } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const props = defineProps({
  show: Boolean,
  imageSrc: String,
});

const emit = defineEmits(['close', 'cropped']);

const cropperRef = ref(null);

const close = () => {
  emit('close');
};

const onChange = (result) => {
  // Can be used for live preview updates if needed
};

const crop = () => {
  if (cropperRef.value) {
    const { canvas } = cropperRef.value.getResult();
    if (canvas) {
      canvas.toBlob((blob) => {
        emit('cropped', blob);
        close();
      }, 'image/jpeg', 0.9);
    }
  }
};
</script>

<style scoped>
.cropper-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.85); /* Semi-transparent dark background */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: var(--space-md);
}

.cropper-modal {
  width: 100%;
  max-width: 500px;
  background: var(--bg-primary, #1a1a1a);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.cropper-header {
  padding: var(--space-md) var(--space-lg);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
}

.cropper-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
}

.btn-close {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 1.5rem;
  cursor: pointer;
  transition: color 0.2s ease;
  line-height: 1;
}

.btn-close:hover {
  color: white;
}

.cropper-body {
  padding: var(--space-lg);
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  background: rgba(0, 0, 0, 0.5); /* Makes the image pop out */
}

.cropper-wrapper {
  width: 100%;
  height: 400px; /* Max height for the cropper */
  max-height: 50vh;
}

.advanced-cropper {
  width: 100%;
  height: 100%;
  background: transparent;
}

.cropper-footer {
  padding: var(--space-md) var(--space-lg);
  display: flex;
  justify-content: flex-end;
  gap: var(--space-sm);
  border-top: 1px solid var(--border-color);
}

@media (max-width: 640px) {
  .cropper-modal {
    height: auto;
    max-height: 90vh;
  }
  .cropper-wrapper {
    height: 300px;
  }
}
</style>
