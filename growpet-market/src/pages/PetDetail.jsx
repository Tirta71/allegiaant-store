import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import PetDetailInfo from "../components/pet/PetDetailInfo";
import Button from "../components/ui/Button";
import { useCart } from "../context/useCart";
import { useAlert } from "../context/useAlert";
import { usePendingPaymentGuard } from "../hooks/usePendingPaymentGuard";
import { fetchProduct } from "../features/products/products.api";

function PetDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { addToCart } = useCart();
  const { showAlert } = useAlert();
  const guardPendingPayment = usePendingPaymentGuard();
  const [pet, setPet] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [quantity, setQuantity] = useState(1);
  const [selectedMutation, setSelectedMutation] = useState("");
  const [selectedWeight, setSelectedWeight] = useState("");
  const availableMutations = useMemo(() => {
    if (!pet) {
      return [];
    }

    return [
      ...new Set(
        pet.variants.map((variant) => variant.mutation).filter(Boolean),
      ),
    ];
  }, [pet]);
  const availableWeights = useMemo(() => {
    if (!pet || !selectedMutation) {
      return [];
    }

    return pet.variants
      .filter((variant) => variant.mutation === selectedMutation)
      .map((variant) => Number(variant.weightKg))
      .filter((weight, index, weights) => weights.indexOf(weight) === index)
      .sort((firstWeight, secondWeight) => firstWeight - secondWeight);
  }, [pet, selectedMutation]);
  const selectedVariant = useMemo(() => {
    if (!pet) {
      return null;
    }

    return (
      pet.variants.find(
        (variant) =>
          variant.mutation === selectedMutation &&
          Number(variant.weightKg) === Number(selectedWeight),
      ) || null
    );
  }, [pet, selectedMutation, selectedWeight]);
  const selectedPrice = selectedVariant?.price || pet?.price || 0;
  const selectedStock = selectedVariant?.stock ?? pet?.stock ?? 0;
  const canBuy = Boolean(selectedVariant && selectedStock > 0);
  const orderQuantity = canBuy ? Math.min(quantity, selectedStock) : 1;

  useEffect(() => {
    let isMounted = true;

    async function loadPet() {
      setIsLoading(true);
      setError("");

      try {
        const nextPet = await fetchProduct(id);

        if (!isMounted) {
          return;
        }

        setPet(nextPet);
        setSelectedMutation(nextPet.variants[0]?.mutation || "");
        setSelectedWeight(nextPet.variants[0]?.weightKg || "");
      } catch (requestError) {
        if (isMounted) {
          setError(
            requestError.status === 404
              ? "Pet tidak ditemukan."
              : requestError.message,
          );
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    loadPet();

    return () => {
      isMounted = false;
    };
  }, [id]);

  function handleMutationChange(mutation) {
    const nextWeight = pet?.variants.find(
      (variant) => variant.mutation === mutation,
    )?.weightKg;

    setSelectedMutation(mutation);
    setSelectedWeight(nextWeight || "");
  }

  if (isLoading) {
    return (
      <div className="container page-flow">
        <section className="empty-state">
          <h1>Memuat pet</h1>
          <p>Mengambil detail product dari backend.</p>
        </section>
      </div>
    );
  }

  if (!pet || error) {
    return (
      <div className="container page-flow">
        <section className="empty-state">
          <h1>Pet tidak ditemukan</h1>
          <p>{error || "Pet ini tidak ada di katalog backend."}</p>
          <Button as={Link} to="/">
            Kembali ke market
          </Button>
        </section>
      </div>
    );
  }

  function handleQuantityChange(value) {
    const nextValue = Number(value);

    if (Number.isNaN(nextValue)) {
      setQuantity(1);
      return;
    }

    setQuantity(Math.max(1, Math.min(nextValue, Math.max(1, selectedStock))));
  }

  async function handleAddToCart() {
    if (await guardPendingPayment()) {
      return;
    }

    if (!canBuy) {
      showAlert({
        tone: "warning",
        title: "Varian kosong",
        message: "Pilih varian pet yang masih punya stok.",
      });
      return;
    }

    addToCart(pet, orderQuantity, {
      productVariantId: selectedVariant.id,
      mutation: selectedMutation,
      weightKg: selectedWeight,
      price: selectedPrice,
      stock: selectedStock,
    });
    showAlert({
      tone: "success",
      title: "Masuk cart",
      message: `${orderQuantity} ${pet.name} ${selectedMutation} siap di checkout.`,
    });
  }

  async function handleBuyNow() {
    if (await guardPendingPayment()) {
      return;
    }

    if (!canBuy) {
      showAlert({
        tone: "warning",
        title: "Varian kosong",
        message: "Pilih varian pet yang masih punya stok.",
      });
      return;
    }

    addToCart(pet, orderQuantity, {
      productVariantId: selectedVariant.id,
      mutation: selectedMutation,
      weightKg: selectedWeight,
      price: selectedPrice,
      stock: selectedStock,
    });
    showAlert({
      tone: "info",
      title: "Order disiapkan",
      message: `${pet.name} masuk cart. Lengkapi data checkout berikutnya.`,
    });
    navigate("/checkout");
  }

  return (
    <div className="container page-flow">
      <Button as={Link} to="/" variant="ghost" className="back-link">
        Back to market
      </Button>
      <PetDetailInfo
        pet={{
          ...pet,
          mutations: availableMutations,
          weights: availableWeights,
          stock: selectedStock,
        }}
        quantity={orderQuantity}
        selectedPrice={selectedPrice}
        selectedMutation={selectedMutation}
        selectedWeight={selectedWeight}
        onQuantityChange={handleQuantityChange}
        onMutationChange={handleMutationChange}
        onWeightChange={setSelectedWeight}
        onAddToCart={handleAddToCart}
        onBuyNow={handleBuyNow}
        canBuy={canBuy}
      />
    </div>
  );
}

export default PetDetail;
