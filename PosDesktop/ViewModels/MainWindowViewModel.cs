using System;
using System.Collections.ObjectModel;
using System.Globalization;
using System.Linq;
using System.Threading.Tasks;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using PosDesktop.Models.Api;
using PosDesktop.Services;

namespace PosDesktop.ViewModels;

public enum AppScreen
{
    Login,
    Shift,
    Pos,
}

public partial class MainWindowViewModel : ViewModelBase
{
    private readonly PosApiClient _apiClient;
    private string? _accessToken;

    public MainWindowViewModel()
    {
        var config = ConfigLoader.Load();
        ApiBaseUrl = config.ApiBaseUrl;
        _apiClient = new PosApiClient(config.ApiBaseUrl);

        _ = TryRestoreSessionAsync();
    }

    public string ApiBaseUrl { get; }

    public ObservableCollection<ProductDto> Products { get; } = [];
    public ObservableCollection<CartDto> Carts { get; } = [];
    public ObservableCollection<CartItemDto> CartItems { get; } = [];
    public IReadOnlyList<string> PaymentMethods { get; } = ["Наличными", "Карта", "В долг"];
    public IReadOnlyList<string> DiscountTypes { get; } = ["fixed", "percent"];

    [ObservableProperty]
    private AppScreen _currentScreen = AppScreen.Login;

    [ObservableProperty]
    private bool _isBusy;

    [ObservableProperty]
    private string _errorMessage = string.Empty;

    [ObservableProperty]
    private string _statusMessage = string.Empty;

    [ObservableProperty]
    private UserDto? _currentUser;

    [ObservableProperty]
    private ShiftDto? _currentShift;

    [ObservableProperty]
    private CartDto? _selectedCart;

    [ObservableProperty]
    private string _phone = string.Empty;

    [ObservableProperty]
    private string _password = string.Empty;

    [ObservableProperty]
    private string _initialCashInput = "0";

    [ObservableProperty]
    private string _finalCashInput = "0";

    [ObservableProperty]
    private string _searchText = string.Empty;

    [ObservableProperty]
    private string _barcodeInput = string.Empty;

    [ObservableProperty]
    private bool _isDiscountDialogOpen;

    [ObservableProperty]
    private string _discountType = "fixed";

    [ObservableProperty]
    private string _discountValueInput = string.Empty;

    [ObservableProperty]
    private bool _isCheckoutDialogOpen;

    [ObservableProperty]
    private string _paymentMethod = "Наличными";

    [ObservableProperty]
    private string _cashInput = "0";

    [ObservableProperty]
    private string _customerName = string.Empty;

    [ObservableProperty]
    private string _customerPhone = string.Empty;

    [ObservableProperty]
    private string _orderNote = string.Empty;

    [ObservableProperty]
    private bool _isCloseShiftDialogOpen;

    public bool IsLoginScreen => CurrentScreen == AppScreen.Login;
    public bool IsShiftScreen => CurrentScreen == AppScreen.Shift;
    public bool IsPosScreen => CurrentScreen == AppScreen.Pos;
    public bool IsDebtPayment => PaymentMethod == "В долг";
    public bool HasErrorMessage => !string.IsNullOrWhiteSpace(ErrorMessage);
    public bool HasStatusMessage => !string.IsNullOrWhiteSpace(StatusMessage);

    public int CartCount => Carts.Count;
    public int ProductCount => Products.Count;
    public int SelectedCartItemsCount => SelectedCart?.Items.Count ?? 0;
    public decimal Subtotal => SelectedCart?.Subtotal ?? 0;
    public decimal DiscountTotal => (SelectedCart?.Discount ?? 0) + CartItems.Sum(x => x.Discount);
    public decimal Total => SelectedCart?.Total ?? 0;
    public decimal ChangeAmount => ParseMoney(CashInput) - Total;

    public string ShiftNumberLabel => CurrentShift is null ? "-" : $"Смена №{CurrentShift.Id}";
    public string UserNameLabel => CurrentUser?.Name ?? "Кассир";

    partial void OnCurrentScreenChanged(AppScreen value)
    {
        OnPropertyChanged(nameof(IsLoginScreen));
        OnPropertyChanged(nameof(IsShiftScreen));
        OnPropertyChanged(nameof(IsPosScreen));
    }

    partial void OnCurrentShiftChanged(ShiftDto? value)
    {
        OnPropertyChanged(nameof(ShiftNumberLabel));
    }

    partial void OnCurrentUserChanged(UserDto? value)
    {
        OnPropertyChanged(nameof(UserNameLabel));
    }

    partial void OnSelectedCartChanged(CartDto? value)
    {
        CartItems.Clear();
        if (value is not null)
        {
            foreach (var item in value.Items)
            {
                CartItems.Add(item);
            }
        }

        OnPropertyChanged(nameof(SelectedCartItemsCount));
        OnPropertyChanged(nameof(Subtotal));
        OnPropertyChanged(nameof(DiscountTotal));
        OnPropertyChanged(nameof(Total));
        OnPropertyChanged(nameof(ChangeAmount));
    }

    partial void OnCashInputChanged(string value)
    {
        OnPropertyChanged(nameof(ChangeAmount));
    }

    partial void OnPaymentMethodChanged(string value)
    {
        OnPropertyChanged(nameof(IsDebtPayment));
    }

    partial void OnErrorMessageChanged(string value)
    {
        OnPropertyChanged(nameof(HasErrorMessage));
    }

    partial void OnStatusMessageChanged(string value)
    {
        OnPropertyChanged(nameof(HasStatusMessage));
    }

    [RelayCommand]
    private async Task LoginAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            if (string.IsNullOrWhiteSpace(Phone) || string.IsNullOrWhiteSpace(Password))
            {
                throw new InvalidOperationException("Введите номер телефона и пароль");
            }

            var response = await _apiClient.LoginAsync(new LoginRequest
            {
                Phone = Phone.Trim(),
                Password = Password,
                DeviceName = Environment.MachineName,
            });

            _accessToken = response.AccessToken;
            _apiClient.SetBearerToken(response.AccessToken);
            SessionStore.SaveToken(response.AccessToken);
            CurrentUser = response.User;

            Password = string.Empty;

            await LoadScreenByShiftAsync();
            StatusMessage = "Успешный вход";
        });
    }

    [RelayCommand]
    private async Task LogoutAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            if (!string.IsNullOrWhiteSpace(_accessToken))
            {
                try
                {
                    await _apiClient.LogoutAsync();
                }
                catch
                {
                    // Ignore logout failure, local session cleanup is enough.
                }
            }

            ClearSession();
            StatusMessage = "Вы вышли из системы";
        });
    }

    [RelayCommand]
    private async Task OpenShiftAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            var initialCash = ParseMoney(InitialCashInput);
            var response = await _apiClient.OpenShiftAsync(initialCash);
            CurrentShift = response.Shift;
            CurrentScreen = AppScreen.Pos;
            await LoadPosDataAsync();
            StatusMessage = "Смена открыта";
        });
    }

    [RelayCommand]
    private void ToggleCloseShiftDialog()
    {
        IsCloseShiftDialogOpen = !IsCloseShiftDialogOpen;
    }

    [RelayCommand]
    private async Task CloseShiftAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            var finalCash = ParseMoney(FinalCashInput);
            var response = await _apiClient.CloseShiftAsync(finalCash);

            CurrentShift = response.Shift;
            IsCloseShiftDialogOpen = false;
            CurrentScreen = AppScreen.Shift;
            Carts.Clear();
            CartItems.Clear();
            Products.Clear();
            SelectedCart = null;

            StatusMessage = "Смена закрыта";
        });
    }

    [RelayCommand]
    private async Task RefreshProductsAsync()
    {
        await ExecuteBusyAsync(async () => { await LoadProductsAsync(); });
    }

    [RelayCommand]
    private async Task AddByBarcodeAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            if (string.IsNullOrWhiteSpace(BarcodeInput))
            {
                return;
            }

            await AddItemToCurrentCartAsync(new AddCartItemRequest
            {
                Sku = BarcodeInput.Trim(),
                Quantity = 1,
            });

            BarcodeInput = string.Empty;
        });
    }

    [RelayCommand]
    private async Task AddProductToCartAsync(ProductDto? product)
    {
        if (product is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            await AddItemToCurrentCartAsync(new AddCartItemRequest
            {
                ProductId = product.Id,
                Quantity = 1,
            });
        });
    }

    [RelayCommand]
    private async Task SelectCartAsync(CartDto? cart)
    {
        if (cart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            var response = await _apiClient.GetCartAsync(cart.Id);
            UpsertCart(response.Cart);
            SelectedCart = response.Cart;
        });
    }

    [RelayCommand]
    private async Task NewCartAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            var response = await _apiClient.CreateCartAsync();
            await RefreshCartsAsync(response.Cart.Id);
            StatusMessage = "Новая корзина создана";
        });
    }

    [RelayCommand]
    private async Task ResetCartAsync()
    {
        if (SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            await _apiClient.DeleteCartAsync(SelectedCart.Id);
            await RefreshCartsAsync();
            StatusMessage = "Корзина очищена";
        });
    }

    [RelayCommand]
    private async Task IncrementItemAsync(CartItemDto? item)
    {
        if (item is null || SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            var response = await _apiClient.UpdateCartItemAsync(
                SelectedCart.Id,
                item.Id,
                new UpdateCartItemRequest
                {
                    Quantity = item.Quantity + 1,
                });

            UpsertCart(response.Cart);
            SelectedCart = response.Cart;
        });
    }

    [RelayCommand]
    private async Task DecrementItemAsync(CartItemDto? item)
    {
        if (item is null || SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            if (item.Quantity <= 1)
            {
                var removeResponse = await _apiClient.RemoveCartItemAsync(SelectedCart.Id, item.Id);
                UpsertCart(removeResponse.Cart);
                SelectedCart = removeResponse.Cart;
                return;
            }

            var updateResponse = await _apiClient.UpdateCartItemAsync(
                SelectedCart.Id,
                item.Id,
                new UpdateCartItemRequest
                {
                    Quantity = item.Quantity - 1,
                });

            UpsertCart(updateResponse.Cart);
            SelectedCart = updateResponse.Cart;
        });
    }

    [RelayCommand]
    private async Task RemoveItemAsync(CartItemDto? item)
    {
        if (item is null || SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            var response = await _apiClient.RemoveCartItemAsync(SelectedCart.Id, item.Id);
            UpsertCart(response.Cart);
            SelectedCart = response.Cart;
        });
    }

    [RelayCommand]
    private void ToggleDiscountDialog()
    {
        IsDiscountDialogOpen = !IsDiscountDialogOpen;
    }

    [RelayCommand]
    private async Task ApplyDiscountAsync()
    {
        if (SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            if (string.IsNullOrWhiteSpace(DiscountValueInput))
            {
                throw new InvalidOperationException("Введите сумму скидки");
            }

            var discountValue = ParseMoney(DiscountValueInput);
            var response = await _apiClient.ApplyDiscountAsync(SelectedCart.Id, new ApplyDiscountRequest
            {
                Type = DiscountType,
                Value = discountValue,
            });

            UpsertCart(response.Cart);
            SelectedCart = response.Cart;
            IsDiscountDialogOpen = false;
            DiscountValueInput = string.Empty;
            StatusMessage = "Скидка применена";
        });
    }

    [RelayCommand]
    private void ToggleCheckoutDialog()
    {
        IsCheckoutDialogOpen = !IsCheckoutDialogOpen;
    }

    [RelayCommand]
    private async Task CheckoutAsync()
    {
        if (SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            var request = new CheckoutRequest
            {
                PaymentMethod = PaymentMethod,
                PaymentStatus = IsDebtPayment ? "debt" : "paid",
                Cash = ParseMoney(CashInput),
                CustomerName = string.IsNullOrWhiteSpace(CustomerName) ? null : CustomerName.Trim(),
                CustomerPhone = string.IsNullOrWhiteSpace(CustomerPhone) ? null : CustomerPhone.Trim(),
                Notes = string.IsNullOrWhiteSpace(OrderNote) ? null : OrderNote.Trim(),
            };

            if (request.PaymentStatus == "debt" && string.IsNullOrWhiteSpace(request.CustomerPhone))
            {
                throw new InvalidOperationException("Для оплаты в долг укажите телефон клиента");
            }

            await _apiClient.CheckoutAsync(SelectedCart.Id, request);
            await RefreshCartsAsync();
            await LoadProductsAsync();
            IsCheckoutDialogOpen = false;
            CashInput = "0";
            CustomerName = string.Empty;
            CustomerPhone = string.Empty;
            OrderNote = string.Empty;
            PaymentMethod = "Наличными";

            StatusMessage = "Продажа оформлена";
        });
    }

    [RelayCommand]
    private async Task RefreshAllAsync()
    {
        await ExecuteBusyAsync(async () => { await LoadPosDataAsync(); });
    }

    private async Task TryRestoreSessionAsync()
    {
        var token = SessionStore.LoadToken();
        if (string.IsNullOrWhiteSpace(token))
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            _accessToken = token;
            _apiClient.SetBearerToken(token);
            var me = await _apiClient.MeAsync();
            CurrentUser = me.User;
            await LoadScreenByShiftAsync();
            StatusMessage = "Сессия восстановлена";
        }, silentError: true);
    }

    private async Task LoadScreenByShiftAsync()
    {
        var shiftResponse = await _apiClient.GetCurrentShiftAsync();
        CurrentShift = shiftResponse.Shift;

        if (shiftResponse.Shift is not null && shiftResponse.Shift.Status == "open")
        {
            CurrentScreen = AppScreen.Pos;
            await LoadPosDataAsync();
            return;
        }

        CurrentScreen = AppScreen.Shift;
    }

    private async Task LoadPosDataAsync()
    {
        await LoadProductsAsync();
        await RefreshCartsAsync();
    }

    private async Task LoadProductsAsync()
    {
        var response = await _apiClient.GetProductsAsync(SearchText);
        Products.Clear();
        foreach (var product in response.Items)
        {
            Products.Add(product);
        }

        OnPropertyChanged(nameof(ProductCount));
    }

    private async Task RefreshCartsAsync(int? preferredCartId = null)
    {
        var response = await _apiClient.GetCartsAsync();
        var carts = response.Items.OrderByDescending(x => x.Id).ToList();

        if (carts.Count == 0)
        {
            var created = await _apiClient.CreateCartAsync();
            carts = [created.Cart];
        }

        Carts.Clear();
        foreach (var cart in carts)
        {
            Carts.Add(cart);
        }

        OnPropertyChanged(nameof(CartCount));

        var selectedId = preferredCartId ?? SelectedCart?.Id;
        SelectedCart = carts.FirstOrDefault(c => c.Id == selectedId) ?? carts.First();
    }

    private async Task AddItemToCurrentCartAsync(AddCartItemRequest request)
    {
        if (SelectedCart is null)
        {
            await RefreshCartsAsync();
        }

        if (SelectedCart is null)
        {
            throw new InvalidOperationException("Корзина не найдена");
        }

        var response = await _apiClient.AddItemToCartAsync(SelectedCart.Id, request);
        UpsertCart(response.Cart);
        SelectedCart = response.Cart;
    }

    private void UpsertCart(CartDto updated)
    {
        var existing = Carts.FirstOrDefault(c => c.Id == updated.Id);
        if (existing is null)
        {
            Carts.Insert(0, updated);
            OnPropertyChanged(nameof(CartCount));
            return;
        }

        var index = Carts.IndexOf(existing);
        Carts[index] = updated;
    }

    private async Task ExecuteBusyAsync(Func<Task> action, bool silentError = false)
    {
        if (IsBusy)
        {
            return;
        }

        try
        {
            IsBusy = true;
            if (!silentError)
            {
                ErrorMessage = string.Empty;
                StatusMessage = string.Empty;
            }

            await action();
        }
        catch (ApiException ex)
        {
            if (!silentError)
            {
                ErrorMessage = ex.Message;
            }

            if (ex.StatusCode == 401)
            {
                ClearSession();
            }
        }
        catch (Exception ex)
        {
            if (!silentError)
            {
                ErrorMessage = ex.Message;
            }
        }
        finally
        {
            IsBusy = false;
        }
    }

    private void ClearSession()
    {
        SessionStore.Clear();
        _accessToken = null;
        _apiClient.ClearBearerToken();
        CurrentUser = null;
        CurrentShift = null;
        CurrentScreen = AppScreen.Login;
        Carts.Clear();
        Products.Clear();
        CartItems.Clear();
        SelectedCart = null;
        OnPropertyChanged(nameof(CartCount));
        OnPropertyChanged(nameof(ProductCount));
    }

    private static decimal ParseMoney(string? raw)
    {
        if (string.IsNullOrWhiteSpace(raw))
        {
            return 0;
        }

        var normalized = raw.Trim().Replace(" ", string.Empty).Replace(',', '.');
        if (decimal.TryParse(normalized, NumberStyles.Any, CultureInfo.InvariantCulture, out var value))
        {
            return value;
        }

        if (decimal.TryParse(raw, NumberStyles.Any, CultureInfo.CurrentCulture, out value))
        {
            return value;
        }

        throw new InvalidOperationException($"Неверный формат суммы: {raw}");
    }
}
