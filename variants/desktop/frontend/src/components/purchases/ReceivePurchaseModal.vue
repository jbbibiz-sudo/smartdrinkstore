<!-- 
  Composant: ReceivePurchaseModal.vue
  Chemin: C:\smartdrinkstore\desktop-app\src\components\purchases\ReceivePurchaseModal.vue
-->

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <!-- En-tête -->
      <div class="modal-header">
        <h2 class="modal-title">
          <span class="icon">📥</span>
          Réception de Marchandises
        </h2>
        <button @click="$emit('close')" class="btn-close">✕</button>
      </div>

      <!-- Corps -->
      <div class="modal-body">
        <!-- ================================== -->
        <!-- VUE POUR L'ÉCRAN (NON IMPRIMABLE) -->
        <!-- ================================== -->
        <div class="no-print">
          <!-- Informations achat -->
          <div class="purchase-info">
            <div class="info-row">
              <span class="info-label">Référence :</span>
              <strong>{{ purchase.reference }}</strong>
            </div>
            <div class="info-row">
              <span class="info-label">Fournisseur :</span>
              <strong>{{ purchase.supplier?.name }}</strong>
            </div>
            <div class="info-row">
              <span class="info-label">Date de commande :</span>
              <strong>{{ formatDate(purchase.order_date) }}</strong>
            </div>
          </div>

          <!-- Liste des produits à recevoir -->
          <div class="receive-section">
            <h3 class="section-title">📦 Produits Commandés</h3>

            <div class="products-list">
              <div
                v-for="(item, index) in receiveForm.items"
                :key="item.id"
                class="product-card"
              >
                <div class="product-header">
                  <div class="product-info">
                    <h4 class="product-name">{{ item.product_name }}</h4>
                    <div class="product-meta">
                      Commandé : <strong>{{ item.quantity }}</strong> unités
                    </div>
                  </div>
                </div>

                <div class="product-receive">
                  <div class="form-group">
                    <label class="form-label">Quantité reçue *</label>
                    <input
                      v-model.number="item.quantity_received"
                      type="number"
                      :class="{ 'input-error': item.quantity_received > item.quantity || item.quantity_received < 0 }"
                      class="form-input"
                      :min="0"
                      :max="item.quantity"
                      required
                    />
                    <div v-if="item.quantity_received > item.quantity" class="error-message">
                      La quantité reçue ne peut pas dépasser la quantité commandée ({{ item.quantity }}).
                    </div>
                    <div v-if="item.quantity_received < 0" class="error-message">
                      La quantité ne peut pas être négative.
                    </div>
                  </div>

                  <div class="receive-status">
                    <div v-if="item.quantity_received === 0" class="status not-received">
                      ❌ Non reçu
                    </div>
                    <div v-else-if="item.quantity_received < item.quantity" class="status partial">
                      ⚠️ Réception partielle ({{ item.quantity - item.quantity_received }} manquant{{ item.quantity - item.quantity_received > 1 ? 's' : '' }})
                    </div>
                    <div v-else class="status complete">
                      ✅ Réception complète
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Date de réception -->
            <div class="form-group">
              <label class="form-label">Date de réception</label>
              <input
                v-model="receiveForm.received_date"
                type="date"
                class="form-input"
              />
            </div>

            <!-- Notes -->
            <div class="form-group" style="margin-top: 1rem;">
              <label class="form-label">Notes de réception</label>
              <textarea
                v-model="receiveForm.notes"
                class="form-textarea"
                rows="2"
                placeholder="Observations sur la réception (état des cartons, remarques...)"
              ></textarea>
            </div>
          </div>

          <!-- Résumé -->
          <div class="summary-section">
            <h3 class="section-title">📊 Résumé de la Réception</h3>

            <div class="summary-grid">
              <div class="summary-card">
                <div class="summary-icon">📦</div>
                <div class="summary-content">
                  <div class="summary-value">{{ receivedCount }}</div>
                  <div class="summary-label">Produits reçus</div>
                </div>
              </div>

              <div class="summary-card">
                <div class="summary-icon">✅</div>
                <div class="summary-content">
                  <div class="summary-value">{{ totalReceived }}</div>
                  <div class="summary-label">Unités reçues</div>
                </div>
              </div>

              <div class="summary-card">
                <div class="summary-icon">📋</div>
                <div class="summary-content">
                  <div class="summary-value">{{ totalOrdered }}</div>
                  <div class="summary-label">Unités commandées</div>
                </div>
              </div>

              <div class="summary-card" :class="receptionStatusClass">
                <div class="summary-icon">{{ receptionStatusIcon }}</div>
                <div class="summary-content">
                  <div class="summary-value">{{ receptionPercentage }}%</div>
                  <div class="summary-label">Taux de réception</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Avertissement réception partielle -->
          <div v-if="hasPartialReception" class="alert alert-warning">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
              <strong>Réception partielle détectée</strong>
              <p>Certains produits n'ont pas été reçus en totalité. Le stock sera mis à jour uniquement pour les quantités reçues.</p>
            </div>
          </div>

          <!-- Info mise à jour -->
          <div class="info-box">
            <div class="info-icon">ℹ️</div>
            <div class="info-content">
              <strong>Actions automatiques après validation :</strong>
              <ul>
                <li>✅ Mise à jour automatique du stock</li>
                <li v-if="purchase.has_deposits">✅ Création des consignes entrantes</li>
                <li>✅ Changement du statut à "Reçu"</li>
                <li>✅ Enregistrement des mouvements de stock</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- ================================== -->
        <!-- VUE POUR L'IMPRESSION -->
        <!-- ================================== -->
        <div class="print-only">
          <div class="receipt-border">
          <div class="print-header">
            <div class="company-branding">
              <!-- Logo (remplacez /logo.png par le chemin réel si disponible) -->
            <img :src="companyInfo.logo" alt="Logo" class="print-logo" onerror="this.style.display='none'" />
              <div class="company-text">
                <h1>{{ companyInfo.name }}</h1>
                <p>{{ companyInfo.subtitle }}</p>
                <p>{{ companyInfo.address }}</p>
                <p v-if="companyInfo.phone">Tél : {{ companyInfo.phone }}</p>
              </div>
            </div>
            <div class="document-details">
              <h2>BON DE RÉCEPTION</h2>
              <div class="detail-row"><strong>Réf:</strong> {{ purchase.reference }}</div>
              <div class="detail-row"><strong>Date:</strong> {{ formatDate(receiveForm.received_date) }}</div>
              <div style="margin-top: 0.5rem; display: flex; justify-content: flex-end; align-items: center; gap: 1rem;">
                <canvas id="purchase-qrcode"></canvas>
                <svg id="purchase-barcode"></svg>
              </div>
            </div>
          </div>

          <div class="supplier-banner">
            <span class="label">Fournisseur :</span>
            <span class="value">{{ purchase.supplier?.name }}</span>
          </div>

          <div v-if="receiveForm.notes" class="print-notes">
            <strong>Notes :</strong> {{ receiveForm.notes }}
          </div>

          <table class="print-table">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Qté Commandée</th>
                <th>Qté Reçue</th>
                <th>Manquant</th>
                <th>P.U.</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in receiveForm.items" :key="item.id">
                <td>{{ item.product_name }}</td>
                <td class="text-center">{{ item.quantity }}</td>
                <td class="text-center">{{ item.quantity_received }}</td>
                <td class="text-center">{{ item.quantity - item.quantity_received }}</td>
                <td class="text-right">{{ formatAmount(item.unit_cost) }}</td>
                <td class="text-right">{{ formatAmount(item.quantity_received * item.unit_cost) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="print-summary">
            <div><strong>Total commandé:</strong> {{ totalOrdered }} unités</div>
            <div><strong>Total reçu:</strong> {{ totalReceived }} unités</div>
            <div><strong>Montant Total:</strong> {{ formatAmount(totalReceivedAmount) }}</div>
            <div><strong>Taux de réception:</strong> {{ receptionPercentage }}%</div>
          </div>

          <div class="print-footer">
            <div class="signature-box">
              <p>Signature Magasinier</p>
            </div>
            <div class="signature-box">
              <p>Signature Fournisseur</p>
            </div>
          </div>
          </div>
        </div>
      </div>

      <!-- Pied -->
      <div class="modal-footer">
        <button
          type="button"
          @click="printReceipt"
          class="btn btn-secondary"
        >
          🖨️ Imprimer
        </button>
        <button
          type="button"
          @click="$emit('close')"
          class="btn btn-secondary"
        >
          Annuler
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="submitting || !canSubmit"
          class="btn btn-primary"
        >
          <span v-if="submitting" class="spinner-small"></span>
          <span v-else>📥</span>
          {{ submitting ? 'Réception...' : 'Valider la Réception' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { initPurchaseManagement } from '@/modules/module-14-purchases.js';
import JsBarcode from 'jsbarcode';
import QRCode from 'qrcode';

// Props
const props = defineProps({
  purchase: {
    type: Object,
    required: true,
  },
});

// Émissions
const emit = defineEmits(['close', 'success']);

// État
const submitting = ref(false);
const receiveForm = ref({
  items: [],
  received_date: new Date().toISOString().split('T')[0],
  notes: '',
});

// Informations de l'entreprise pour l'impression
const companyInfo = ref({
  name: localStorage.getItem('app_company_name') || '🍹 SmartDrink Store',
  subtitle: localStorage.getItem('app_company_subtitle') || 'Commerce Général & Distribution',
  address: localStorage.getItem('app_company_address') || 'Yaoundé, Cameroun',
  logo: localStorage.getItem('app_company_logo') || '/logo.png',
  phone: localStorage.getItem('app_company_phone') || ''
});

// Initialiser le module
const state = ref({});
const loaders = { loadProducts: () => {}, loadDeposits: () => {} };
const { receivePurchase, prepareReceiveForm } = initPurchaseManagement(state, loaders);

// Computed
const receivedCount = computed(() => {
  return receiveForm.value.items.filter(item => item.quantity_received > 0).length;
});

const totalReceived = computed(() => {
  return receiveForm.value.items.reduce((sum, item) => sum + item.quantity_received, 0);
});

const totalOrdered = computed(() => {
  return receiveForm.value.items.reduce((sum, item) => sum + item.quantity, 0);
});

const totalReceivedAmount = computed(() => {
  return receiveForm.value.items.reduce((sum, item) => sum + (item.quantity_received * (item.unit_cost || 0)), 0);
});

const receptionPercentage = computed(() => {
  if (totalOrdered.value === 0) return 0;
  return Math.round((totalReceived.value / totalOrdered.value) * 100);
});

const hasPartialReception = computed(() => {
  return receiveForm.value.items.some(item => 
    item.quantity_received > 0 && item.quantity_received < item.quantity
  );
});

const receptionStatusIcon = computed(() => {
  if (receptionPercentage.value === 100) return '✅';
  if (receptionPercentage.value > 0) return '⚠️';
  return '❌';
});

const receptionStatusClass = computed(() => {
  if (receptionPercentage.value === 100) return 'status-complete';
  if (receptionPercentage.value > 0) return 'status-partial';
  return 'status-none';
});

const canSubmit = computed(() => {
  if (totalReceived.value <= 0) return false;

  // Vérifier qu'aucune quantité reçue ne dépasse la quantité commandée
  // et qu'aucune n'est négative.
  const isAnyInvalid = receiveForm.value.items.some(
    item => item.quantity_received > item.quantity || item.quantity_received < 0
  );
  return !isAnyInvalid;
});

// Méthodes
const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-FR').format(amount || 0) + ' FCFA';
};

const printReceipt = () => {
  const printContent = document.querySelector('.print-only');
  if (!printContent) {
    console.error("Element .print-only introuvable pour l'impression.");
    return;
  }

  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  iframe.setAttribute('title', 'Frame d\'impression');
  document.body.appendChild(iframe);

  const iframeDoc = iframe.contentWindow.document;
  iframeDoc.open();
  iframeDoc.write('<!DOCTYPE html><html><head><title>Bon de Réception</title></head><body></body></html>');

  // Cloner tous les styles pour préserver la mise en page
  const styles = document.querySelectorAll('style, link[rel="stylesheet"]');
  styles.forEach(style => {
    iframeDoc.head.appendChild(style.cloneNode(true));
  });

  iframeDoc.body.innerHTML = printContent.innerHTML;
  iframeDoc.close();

  // Attendre que les images soient chargées avant d'imprimer
  const images = Array.from(iframeDoc.querySelectorAll('img'));
  const promises = images.map(img => {
    if (img.complete) return Promise.resolve();
    return new Promise((resolve) => {
      img.onload = img.onerror = resolve;
    });
  });

  Promise.all(promises).then(() => {
    setTimeout(() => {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
      document.body.removeChild(iframe);
    }, 250); // Un court délai pour assurer que tout est bien rendu
  });
};

const handleSubmit = async () => {
  // Validation plus granulaire
  if (!canSubmit.value) {
    const invalidItem = receiveForm.value.items.find(
      item => item.quantity_received > item.quantity || item.quantity_received < 0
    );
    if (invalidItem) {
      alert(`❌ Quantité invalide pour "${invalidItem.product_name}". La quantité doit être entre 0 et ${invalidItem.quantity}.`);
    } else {
      alert('❌ Veuillez saisir au moins une quantité reçue.');
    }
    return;
  }

  const confirmation = confirm(
    `Confirmer la réception de ${totalReceived.value} unité(s) ?\n\n` +
    `Cela va mettre à jour le stock automatiquement.`
  );

  if (!confirmation) return;

  submitting.value = true;

  try {
    const result = await receivePurchase(props.purchase.id, receiveForm.value);

    if (result.success) {
      alert('✅ Réception validée avec succès ! Le stock a été mis à jour.');
      emit('success');
    } else {
      alert('❌ Erreur : ' + result.error);
    }
  } catch (error) {
    alert('❌ Erreur : ' + error.message);
  } finally {
    submitting.value = false;
  }
};

// Initialisation
onMounted(() => {
  // Préparer le formulaire avec les données de l'achat
  receiveForm.value.items = props.purchase.items.map(item => ({
    id: item.id,
    product_id: item.product_id,
    product_name: item.product?.name || 'Produit inconnu',
    quantity: item.quantity,
    unit_cost: item.unit_cost || 0,
    quantity_received: item.quantity_received ?? item.quantity, // Par défaut, tout est reçu
  }));

  // Générer le code-barres et le QR Code
  nextTick(() => {
    if (props.purchase?.reference) {
      const reference = props.purchase.reference;
      
      // Contenu du QR Code : Par défaut la référence. 
      // Pour une URL, remplacez par : `https://votre-site.com/suivi/${reference}`
      const qrContent = reference;

      // Générer le code-barres
      try {
        JsBarcode("#purchase-barcode", reference, {
          format: "CODE128",
          width: 1.5,
          height: 40,
          displayValue: false, // On masque le texte car la référence est déjà affichée au-dessus
          margin: 0
        });
      } catch (e) {
        console.error("Erreur génération code-barres:", e);
      }

      // Générer le QR Code
      const qrCanvas = document.getElementById('purchase-qrcode');
      if (qrCanvas) {
        QRCode.toCanvas(qrCanvas, qrContent, {
          width: 50,
          margin: 1,
          errorCorrectionLevel: 'H'
        }, function (error) {
          if (error) console.error('Erreur génération QR Code:', error)
        });
      }
    }
  });
});
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-close {
  width: 36px;
  height: 36px;
  border: none;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #e2e8f0;
  transform: rotate(90deg);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.purchase-info {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}

.info-label {
  color: #64748b;
}

.receive-section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1rem 0;
}

.products-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.product-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 2px solid #e2e8f0;
}

.product-name {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.product-meta {
  color: #64748b;
  font-size: 0.875rem;
}

.product-receive {
  display: flex;
  align-items: flex-end;
  gap: 2rem;
  margin-top: 1rem;
}

.form-group {
  flex: 1;
}

.form-label {
  font-weight: 600;
  color: #475569;
  font-size: 0.875rem;
  display: block;
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.input-error {
  border-color: #ef4444; /* red-500 */
}

.input-error:focus {
  border-color: #ef4444;
}

.error-message {
  color: #b91c1c; /* red-800 */
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
.receive-status {
  flex: 1;
}

.status {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  text-align: center;
}

.status.complete {
  background: #d1fae5;
  color: #065f46;
}

.status.partial {
  background: #fef3c7;
  color: #92400e;
}

.status.not-received {
  background: #fee2e2;
  color: #991b1b;
}

.summary-section {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 1rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.summary-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 2px solid #e2e8f0;
}

.summary-card.status-complete {
  border-color: #10b981;
  background: #d1fae5;
}

.summary-card.status-partial {
  border-color: #f59e0b;
  background: #fef3c7;
}

.summary-icon {
  font-size: 2rem;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}

.summary-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.alert {
  padding: 1rem 1.5rem;
  border-radius: 12px;
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.alert-warning {
  background: #fef3c7;
  border: 2px solid #f59e0b;
}

.alert-icon {
  font-size: 1.5rem;
}

.alert-content strong {
  display: block;
  margin-bottom: 0.5rem;
  color: #92400e;
}

.alert-content p {
  margin: 0;
  color: #78350f;
}

.info-box {
  background: #eff6ff;
  border: 2px solid #3b82f6;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
}

.info-icon {
  font-size: 1.5rem;
}

.info-content ul {
  margin: 0.5rem 0 0 0;
  padding-left: 1.5rem;
}

.info-content li {
  margin-bottom: 0.25rem;
  color: #1e40af;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 2px solid #e2e8f0;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.print-only {
  display: none;
}

@media print {
  .no-print {
    display: none;
  }

  .print-only {
    display: block;
    font-family: 'Courier New', Courier, monospace;
    color: black;
  }

  .receipt-border {
    border: 2px solid #000;
    padding: 1rem;
    font-family: 'Courier New', Courier, monospace;
    color: black;
  }

  .modal-overlay {
    position: static;
    background: white;
    padding: 0;
  }

  .modal-container {
    box-shadow: none;
    border: none;
    max-width: 100%;
    max-height: none;
    padding: 1rem;
  }

  .modal-body {
    padding: 0;
    overflow: visible;
  }

  .print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 1.5rem;
    border-bottom: 2px solid #000;
    background-color: #f1f5f9; /* Couleur de fond (Gris clair) */
    -webkit-print-color-adjust: exact; /* Force l'impression de la couleur */
    print-color-adjust: exact;
  }

  .company-branding {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .print-logo {
    max-height: 60px;
    max-width: 100px;
  }

  .company-text h1 {
    font-size: 1.4rem;
    margin: 0;
    color: #000;
  }

  .company-text p {
    margin: 0;
    font-size: 0.85rem;
    color: #444;
  }

  .document-details {
    text-align: right;
  }

  .document-details h2 {
    font-size: 1.6rem;
    margin: 0 0 0.5rem 0;
    text-transform: uppercase;
    color: #000;
  }

  .print-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2rem;
  }

  .print-table th,
  .print-table td {
    border: 1px solid #ccc;
    padding: 0.5rem;
    text-align: left;
  }

  .print-table th {
    background-color: #f2f2f2;
    font-weight: bold;
  }

  .text-center {
    text-align: center;
  }

  .text-right {
    text-align: right;
  }

  .supplier-banner {
    background-color: #f0f0f0;
    padding: 0.75rem;
    border: 1px solid #ccc;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
  }

  .print-notes {
    margin-bottom: 1.5rem;
    padding: 0.5rem;
    border: 1px dashed #ccc;
    background-color: #fafafa;
  }

  .print-summary {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #ccc;
    text-align: right;
  }

  .print-footer {
    margin-top: 4rem;
    display: flex;
    justify-content: space-around;
  }

  .signature-box {
    text-align: center;
    padding-top: 3rem;
    border-top: 1px solid black;
    width: 200px;
  }
}
</style>