using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using System.Globalization;
using System.Linq;
using System.Net.Http;
using System.Threading;
using System.Threading.Tasks;
using Avalonia.Threading;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using PosDesktop.Models.Api;
using PosDesktop.Models.Local;
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
    private readonly LocalPosStore _localStore;
    private readonly SemaphoreSlim _syncGate = new(1, 1);
    private readonly DispatcherTimer _syncTimer;
    private string? _accessToken;
    private bool _didFullActiveProductsSync;

    public MainWindowViewModel()
    {
        var config = ConfigLoader.Load();
        ApiBaseUrl = config.ApiBaseUrl;

        _apiClient = new PosApiClient(config.ApiBaseUrl);
        _localStore = new LocalPosStore();

        _syncTimer = new DispatcherTimer
        {
            Interval = TimeSpan.FromSeconds(6),
        };
        _syncTimer.Tick += SyncTimerOnTick;
        _syncTimer.Start();

        _ = BootstrapAsync();
    }

    public string ApiBaseUrl { get; }

    public ObservableCollection<ProductDto> Products { get; } = [];
    public ObservableCollection<CartDto> Carts { get; } = [];
    public ObservableCollection<CartItemDto> CartItems { get; } = [];
    public IReadOnlyList<string> PaymentMethods { get; } = ["Наличными", "Карта", "В долг"];
    public IReadOnlyList<string> DiscountTypes { get; } = ["fixed", "percent"];
    public IReadOnlyList<string> ItemDiscountTypes { get; } = ["fixed", "percent"];
    public IReadOnlyList<string> ProductStatuses { get; } = ["active", "inactive", "draft"];

    [ObservableProperty]
    private AppScreen _currentScreen = AppScreen.Login;

    [ObservableProperty]
    private bool _isBusy;

    [ObservableProperty]
    private bool _isOfflineMode;

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

    [ObservableProperty]
    private bool _isItemDiscountDialogOpen;

    [ObservableProperty]
    private CartItemDto? _selectedDiscountItem;

    [ObservableProperty]
    private string _itemDiscountValueInput = string.Empty;

    [ObservableProperty]
    private string _itemDiscountType = "fixed";

    [ObservableProperty]
    private bool _isAddExpenseDialogOpen;

    [ObservableProperty]
    private string _expenseTotalInput = string.Empty;

    [ObservableProperty]
    private string _expenseDescriptionInput = string.Empty;

    [ObservableProperty]
    private bool _isPayDebtDialogOpen;

    [ObservableProperty]
    private string _debtPhoneInput = string.Empty;

    [ObservableProperty]
    private string _debtTotalInput = string.Empty;

    [ObservableProperty]
    private string _debtCustomerNameInput = string.Empty;

    [ObservableProperty]
    private string _debtCustomerStatus = string.Empty;

    [ObservableProperty]
    private bool _isAddProductDialogOpen;

    [ObservableProperty]
    private string _newProductSkuInput = string.Empty;

    [ObservableProperty]
    private string _newProductQuantityInput = "1";

    [ObservableProperty]
    private string _newProductNameInput = string.Empty;

    [ObservableProperty]
    private string _newProductPriceInput = string.Empty;

    [ObservableProperty]
    private string _newProductStatus = "active";

    public bool IsLoginScreen => CurrentScreen == AppScreen.Login;
    public bool IsShiftScreen => CurrentScreen == AppScreen.Shift;
    public bool IsPosScreen => CurrentScreen == AppScreen.Pos;
    public bool IsDebtPayment => PaymentMethod == "В долг";
    public bool HasErrorMessage => !string.IsNullOrWhiteSpace(ErrorMessage);
    public bool HasStatusMessage => !string.IsNullOrWhiteSpace(StatusMessage);
    public bool HasDebtCustomerStatus => !string.IsNullOrWhiteSpace(DebtCustomerStatus);

    public int CartCount => Carts.Count;
    public int ProductCount => Products.Count;
    public int SelectedCartItemsCount => SelectedCart?.Items.Count ?? 0;
    public decimal Subtotal => SelectedCart?.Subtotal ?? 0;
    public decimal DiscountTotal => (SelectedCart?.Discount ?? 0) + CartItems.Sum(x => x.Discount);
    public decimal Total => SelectedCart?.Total ?? 0;
    public decimal ChangeAmount => ParseMoney(CashInput) - Total;

    public string ShiftNumberLabel => CurrentShift is null ? "-" : $"Смена №{CurrentShift.Id}";
    public string UserNameLabel => CurrentUser?.Name ?? "Кассир";
    public string ConnectionStatusLabel => IsOfflineMode ? "Оффлайн" : "Онлайн";

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
                CartItems.Add(CloneCartItem(item));
            }
        }

        if (SelectedDiscountItem is not null
            && (value is null || !value.Items.Any(x => x.Id == SelectedDiscountItem.Id)))
        {
            IsItemDiscountDialogOpen = false;
            SelectedDiscountItem = null;
            ItemDiscountValueInput = string.Empty;
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

    partial void OnDebtCustomerStatusChanged(string value)
    {
        OnPropertyChanged(nameof(HasDebtCustomerStatus));
    }

    partial void OnSearchTextChanged(string value)
    {
        if (!IsPosScreen)
        {
            return;
        }

        _ = LoadProductsFromLocalAsync();
    }

    partial void OnBarcodeInputChanged(string value)
    {
        if (!IsPosScreen)
        {
            return;
        }

        _ = LoadProductsFromLocalAsync();
    }

    partial void OnIsOfflineModeChanged(bool value)
    {
        OnPropertyChanged(nameof(ConnectionStatusLabel));
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

            LoginResponse response;
            try
            {
                response = await LoginWithFallbacksAsync(Phone, Password);
            }
            catch (Exception ex) when (IsConnectivityException(ex))
            {
                SetOfflineMode(true);
                throw new InvalidOperationException("Нет сети. Первый вход в систему возможен только онлайн.");
            }

            SetOfflineMode(false);
            _accessToken = response.AccessToken;
            _apiClient.SetBearerToken(response.AccessToken);
            SessionStore.SaveToken(response.AccessToken);

            CurrentUser = response.User;
            await _localStore.SaveCachedUserAsync(response.User);

            Password = string.Empty;

            var gotShiftFromServer = await TryFetchAndCacheCurrentShiftAsync();
            await RouteByShiftStateAsync();
            _ = TrySyncNowAsync(forcePullFromServer: true, silent: true);

            StatusMessage = gotShiftFromServer
                ? "Успешный вход"
                : "Вход выполнен. Оффлайн режим";
        });
    }

    private async Task<LoginResponse> LoginWithFallbacksAsync(string phoneRaw, string passwordRaw)
    {
        var deviceName = Environment.MachineName;
        var phones = BuildPhoneVariants(phoneRaw);
        var passwords = BuildPasswordVariants(passwordRaw);

        Exception? lastError = null;

        foreach (var phone in phones)
        {
            foreach (var password in passwords)
            {
                try
                {
                    return await _apiClient.LoginAsync(new LoginRequest
                    {
                        Phone = phone,
                        Password = password,
                        DeviceName = deviceName,
                    });
                }
                catch (ApiException ex) when (ex.StatusCode == 422 && ex.Message.Contains("Invalid credentials", StringComparison.OrdinalIgnoreCase))
                {
                    lastError = ex;
                }
            }
        }

        if (lastError is not null)
        {
            throw lastError;
        }

        throw new InvalidOperationException("Не удалось выполнить вход");
    }

    private static List<string> BuildPhoneVariants(string rawPhone)
    {
        var list = new List<string>();
        var trimmed = rawPhone.Trim();
        if (!string.IsNullOrWhiteSpace(trimmed))
        {
            list.Add(trimmed);
        }

        var digits = new string(trimmed.Where(char.IsDigit).ToArray());
        if (!string.IsNullOrWhiteSpace(digits))
        {
            list.Add(digits);
            list.Add("+" + digits);
        }

        if (digits.Length == 9)
        {
            list.Add("0" + digits);
            list.Add("992" + digits);
            list.Add("+992" + digits);
        }

        if (digits.StartsWith("992", StringComparison.Ordinal) && digits.Length > 3)
        {
            var withoutCountry = digits[3..];
            list.Add(withoutCountry);
            list.Add("+992" + withoutCountry);
        }

        if (digits.StartsWith("00", StringComparison.Ordinal) && digits.Length > 2)
        {
            var without00 = digits[2..];
            list.Add(without00);
            list.Add("+" + without00);
        }

        if (digits.StartsWith("0", StringComparison.Ordinal) && digits.Length > 1)
        {
            list.Add(digits.TrimStart('0'));
        }

        return list
            .Where(x => !string.IsNullOrWhiteSpace(x))
            .Distinct(StringComparer.Ordinal)
            .ToList();
    }

    private static List<string> BuildPasswordVariants(string rawPassword)
    {
        var trimmed = rawPassword.Trim();
        return new List<string> { rawPassword, trimmed }
            .Where(x => !string.IsNullOrEmpty(x))
            .Distinct(StringComparer.Ordinal)
            .ToList();
    }

    [RelayCommand]
    private async Task LogoutAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            if (!string.IsNullOrWhiteSpace(_accessToken) && !IsOfflineMode)
            {
                try
                {
                    await _apiClient.LogoutAsync();
                }
                catch
                {
                    // Logout API failure should not block local cleanup.
                }
            }

            await ClearSessionAsync();
            StatusMessage = "Вы вышли из системы";
        });
    }

    [RelayCommand]
    private async Task OpenShiftAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            if (CurrentUser is null)
            {
                throw new InvalidOperationException("Требуется авторизация");
            }

            var initialCash = ParseMoney(InitialCashInput);
            ShiftDto shift;
            var synced = false;

            if (!IsOfflineMode)
            {
                try
                {
                    var response = await _apiClient.OpenShiftAsync(initialCash);
                    shift = response.Shift;
                    await _localStore.ClearPendingShiftOpenAsync();
                    synced = true;
                }
                catch (ApiException ex) when (ex.StatusCode == 422 && ex.Message.Contains("already open", StringComparison.OrdinalIgnoreCase))
                {
                    var current = await _apiClient.GetCurrentShiftAsync();
                    shift = current.Shift ?? throw new InvalidOperationException("Смена уже открыта, но сервер не вернул данные");
                    synced = true;
                }
                catch (Exception ex) when (IsConnectivityException(ex))
                {
                    SetOfflineMode(true);
                    shift = BuildLocalOpenShift(initialCash);
                    await _localStore.SetPendingShiftOpenAsync(initialCash);
                }
            }
            else
            {
                shift = BuildLocalOpenShift(initialCash);
                await _localStore.SetPendingShiftOpenAsync(initialCash);
            }

            CurrentShift = shift;
            await _localStore.SaveShiftAsync(shift);

            CurrentScreen = AppScreen.Pos;
            await LoadProductsFromLocalAsync();
            await EnsureActiveCartAsync();

            if (synced)
            {
                _ = TrySyncNowAsync(forcePullFromServer: true, silent: true);
                StatusMessage = "Смена открыта";
            }
            else
            {
                StatusMessage = "Смена открыта офлайн. Синхронизация выполнится при появлении сети";
            }
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
            if (CurrentUser is null)
            {
                throw new InvalidOperationException("Требуется авторизация");
            }

            var finalCash = ParseMoney(FinalCashInput);
            var synced = false;
            ShiftDto closedShift;

            if (!IsOfflineMode)
            {
                try
                {
                    await TrySyncNowAsync(forcePullFromServer: false, silent: true);
                    var response = await _apiClient.CloseShiftAsync(finalCash);
                    closedShift = response.Shift;
                    await _localStore.ClearPendingShiftCloseAsync();
                    await _localStore.ClearPendingShiftOpenAsync();
                    await _localStore.ClearUserCartsAsync(CurrentUser.Id.ToString(CultureInfo.InvariantCulture));
                    synced = true;
                }
                catch (Exception ex) when (IsConnectivityException(ex))
                {
                    SetOfflineMode(true);
                    closedShift = BuildLocalCloseShift(finalCash);
                    await _localStore.SetPendingShiftCloseAsync(finalCash);
                }
            }
            else
            {
                closedShift = BuildLocalCloseShift(finalCash);
                await _localStore.SetPendingShiftCloseAsync(finalCash);
            }

            CurrentShift = closedShift;
            await _localStore.SaveShiftAsync(closedShift);

            IsCloseShiftDialogOpen = false;
            CurrentScreen = AppScreen.Shift;

            if (synced)
            {
                Carts.Clear();
                CartItems.Clear();
                SelectedCart = null;
                OnPropertyChanged(nameof(CartCount));
                StatusMessage = "Смена закрыта";
            }
            else
            {
                StatusMessage = "Закрытие смены поставлено в очередь. Синхронизация выполнится при появлении сети";
            }
        });
    }

    [RelayCommand]
    private async Task ConfirmCloseShiftAsync()
    {
        if (!IsCloseShiftDialogOpen)
        {
            return;
        }

        await CloseShiftAsync();
    }

    [RelayCommand]
    private async Task RefreshProductsAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            await LoadProductsFromLocalAsync();
            _ = TrySyncNowAsync(forcePullFromServer: true, silent: true);
        });
    }

    [RelayCommand]
    private void ToggleAddExpenseDialog()
    {
        IsAddExpenseDialogOpen = !IsAddExpenseDialogOpen;
        if (!IsAddExpenseDialogOpen)
        {
            ResetAddExpenseForm();
        }
    }

    [RelayCommand]
    private async Task AddExpenseAsync()
    {
        if (!IsAddExpenseDialogOpen)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            EnsureOnlineActionAllowed();
            EnsureOpenShift();

            var total = ParseMoney(ExpenseTotalInput);
            if (total <= 0)
            {
                throw new InvalidOperationException("Введите сумму расхода больше 0");
            }

            var response = await _apiClient.AddExpenseAsync(new AddExpenseRequest
            {
                Total = total,
                Description = string.IsNullOrWhiteSpace(ExpenseDescriptionInput)
                    ? null
                    : ExpenseDescriptionInput.Trim(),
            });

            ToggleAddExpenseDialog();
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
            StatusMessage = string.IsNullOrWhiteSpace(response.Message)
                ? "Расход добавлен"
                : response.Message;
        });
    }

    [RelayCommand]
    private void TogglePayDebtDialog()
    {
        IsPayDebtDialogOpen = !IsPayDebtDialogOpen;
        if (!IsPayDebtDialogOpen)
        {
            ResetPayDebtForm();
        }
    }

    [RelayCommand]
    private async Task FindDebtCustomerAsync()
    {
        if (!IsPayDebtDialogOpen)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            EnsureOnlineActionAllowed();

            var phone = DebtPhoneInput.Trim();
            if (string.IsNullOrWhiteSpace(phone))
            {
                throw new InvalidOperationException("Введите телефон клиента");
            }

            var response = await _apiClient.FindCustomerByPhoneAsync(phone);
            if (response.Customer is null)
            {
                DebtCustomerStatus = "Клиент не найден. Будет создан при погашении.";
                return;
            }

            DebtCustomerNameInput = response.Customer.Name;
            DebtCustomerStatus = $"Текущий долг: {response.Customer.Debt:0.##} c";
        });
    }

    [RelayCommand]
    private async Task PayDebtAsync()
    {
        if (!IsPayDebtDialogOpen)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            EnsureOnlineActionAllowed();
            EnsureOpenShift();

            var phone = DebtPhoneInput.Trim();
            if (string.IsNullOrWhiteSpace(phone))
            {
                throw new InvalidOperationException("Введите телефон клиента");
            }

            var total = ParseMoney(DebtTotalInput);
            if (total <= 0)
            {
                throw new InvalidOperationException("Введите сумму погашения больше 0");
            }

            var response = await _apiClient.PayDebtAsync(new PayDebtRequest
            {
                Phone = phone,
                Total = total,
                CustomerName = string.IsNullOrWhiteSpace(DebtCustomerNameInput)
                    ? null
                    : DebtCustomerNameInput.Trim(),
            });

            TogglePayDebtDialog();
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
            StatusMessage = string.IsNullOrWhiteSpace(response.Message)
                ? "Долг успешно погашен"
                : $"{response.Message}. Остаток долга: {response.Customer.Debt:0.##} c";
        });
    }

    [RelayCommand]
    private void ToggleAddProductDialog()
    {
        IsAddProductDialogOpen = !IsAddProductDialogOpen;
        if (!IsAddProductDialogOpen)
        {
            ResetAddProductForm();
        }
    }

    [RelayCommand]
    private async Task SaveProductAsync()
    {
        if (!IsAddProductDialogOpen)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            EnsureOnlineActionAllowed();
            EnsureOpenShift();

            var sku = NewProductSkuInput.Trim();
            if (string.IsNullOrWhiteSpace(sku))
            {
                throw new InvalidOperationException("Введите артикул (SKU)");
            }

            var quantity = ParsePositiveInt(NewProductQuantityInput, "количество");
            decimal? sellingPrice = null;
            if (!string.IsNullOrWhiteSpace(NewProductPriceInput))
            {
                sellingPrice = ParseMoney(NewProductPriceInput);
                if (sellingPrice < 0)
                {
                    throw new InvalidOperationException("Цена не может быть отрицательной");
                }
            }

            var response = await _apiClient.UpsertProductStockAsync(new UpsertProductStockRequest
            {
                Sku = sku,
                Quantity = quantity,
                Name = string.IsNullOrWhiteSpace(NewProductNameInput)
                    ? null
                    : NewProductNameInput.Trim(),
                SellingPrice = sellingPrice,
                Status = string.IsNullOrWhiteSpace(NewProductStatus) ? "active" : NewProductStatus,
            });

            ToggleAddProductDialog();
            await TrySyncNowAsync(forcePullFromServer: true, silent: true);
            StatusMessage = response.Created ? "Товар создан и добавлен на склад" : "Остаток товара обновлен";
        });
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

            await AddItemToCurrentCartLocalAsync(new AddCartItemRequest
            {
                Sku = BarcodeInput.Trim(),
                Quantity = 1,
            });

            BarcodeInput = string.Empty;
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
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
            await AddItemToCurrentCartLocalAsync(new AddCartItemRequest
            {
                ProductId = product.Id,
                Quantity = 1,
            });

            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
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
            if (CurrentUser is null)
            {
                return;
            }

            var record = await _localStore.GetCartRecordAsync(CurrentUser.Id.ToString(CultureInfo.InvariantCulture), cart.Id);
            SelectedCart = record is null ? cart : CloneCart(record.Cart);
        }, clearMessages: false);
    }

    [RelayCommand]
    private async Task NewCartAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            await CreateAndSelectNewLocalCartAsync();
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
            StatusMessage = "Новая корзина создана";
        });
    }

    [RelayCommand]
    private async Task HoldCartAsync()
    {
        if (SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            if (SelectedCart is null)
            {
                return;
            }

            if (SelectedCart.Items.Count == 0)
            {
                throw new InvalidOperationException("Нельзя держать пустую корзину. Добавьте хотя бы 1 товар.");
            }

            await CreateAndSelectNewLocalCartAsync();
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
            StatusMessage = "Заказ удержан. Можно оформить позже.";
        });
    }

    [RelayCommand]
    private async Task ResetCartAsync()
    {
        if (SelectedCart is null || CurrentUser is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            var cart = CloneCart(SelectedCart);
            cart.Items.Clear();
            cart.Discount = 0;
            RecalculateCart(cart);
            await SaveActiveCartAsync(cart, isDirty: true);

            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
            StatusMessage = "Корзина сброшена";
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
            var cart = CloneCart(SelectedCart);
            var localItem = cart.Items.FirstOrDefault(x => x.Id == item.Id);
            if (localItem is null)
            {
                return;
            }

            localItem.Quantity += 1;
            localItem.LineSubtotal = Round2(localItem.Price * localItem.Quantity);

            RecalculateCart(cart);
            await SaveActiveCartAsync(cart, isDirty: true);
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
        }, clearMessages: false);
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
            var cart = CloneCart(SelectedCart);
            var localItem = cart.Items.FirstOrDefault(x => x.Id == item.Id);
            if (localItem is null)
            {
                return;
            }

            if (localItem.Quantity <= 1)
            {
                cart.Items.Remove(localItem);
            }
            else
            {
                localItem.Quantity -= 1;
                localItem.LineSubtotal = Round2(localItem.Price * localItem.Quantity);
            }

            RecalculateCart(cart);
            await SaveActiveCartAsync(cart, isDirty: true);
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
        }, clearMessages: false);
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
            var cart = CloneCart(SelectedCart);
            var localItem = cart.Items.FirstOrDefault(x => x.Id == item.Id);
            if (localItem is null)
            {
                return;
            }

            cart.Items.Remove(localItem);
            RecalculateCart(cart);
            await SaveActiveCartAsync(cart, isDirty: true);
            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
        }, clearMessages: false);
    }

    [RelayCommand]
    private void OpenItemDiscountDialog(CartItemDto? item)
    {
        if (item is null)
        {
            return;
        }

        SelectedDiscountItem = item;
        ItemDiscountValueInput = item.Discount > 0
            ? item.Discount.ToString("0.##", CultureInfo.InvariantCulture)
            : string.Empty;
        ItemDiscountType = "fixed";
        IsItemDiscountDialogOpen = true;
    }

    [RelayCommand]
    private void ToggleItemDiscountDialog()
    {
        IsItemDiscountDialogOpen = !IsItemDiscountDialogOpen;
        if (!IsItemDiscountDialogOpen)
        {
            SelectedDiscountItem = null;
            ItemDiscountValueInput = string.Empty;
            ItemDiscountType = "fixed";
        }
    }

    [RelayCommand]
    private async Task ApplyItemDiscountAsync()
    {
        if (!IsItemDiscountDialogOpen || SelectedCart is null || SelectedDiscountItem is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            if (string.IsNullOrWhiteSpace(ItemDiscountValueInput))
            {
                throw new InvalidOperationException("Введите сумму скидки");
            }

            var cart = CloneCart(SelectedCart);
            var localItem = cart.Items.FirstOrDefault(x => x.Id == SelectedDiscountItem.Id);
            if (localItem is null)
            {
                throw new InvalidOperationException("Товар не найден в корзине");
            }

            var discountValue = ParseMoney(ItemDiscountValueInput);
            var lineSubtotalRaw = localItem.Price * localItem.Quantity;
            var discount = ItemDiscountType == "percent"
                ? Round2((lineSubtotalRaw * discountValue) / 100m)
                : discountValue;

            var cappedDiscount = Math.Min(Math.Max(0, discount), Round2(lineSubtotalRaw));
            localItem.Discount = cappedDiscount;

            RecalculateCart(cart);
            await SaveActiveCartAsync(cart, isDirty: true);

            IsItemDiscountDialogOpen = false;
            SelectedDiscountItem = null;
            ItemDiscountValueInput = string.Empty;
            ItemDiscountType = "fixed";

            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
            StatusMessage = "Скидка на товар применена";
        });
    }

    [RelayCommand]
    private void CloseOpenDialog()
    {
        if (IsAddProductDialogOpen)
        {
            ToggleAddProductDialog();
            return;
        }

        if (IsPayDebtDialogOpen)
        {
            TogglePayDebtDialog();
            return;
        }

        if (IsAddExpenseDialogOpen)
        {
            ToggleAddExpenseDialog();
            return;
        }

        if (IsItemDiscountDialogOpen)
        {
            ToggleItemDiscountDialog();
            return;
        }

        if (IsDiscountDialogOpen)
        {
            ToggleDiscountDialog();
            return;
        }

        if (IsCheckoutDialogOpen)
        {
            ToggleCheckoutDialog();
            return;
        }

        if (IsCloseShiftDialogOpen)
        {
            ToggleCloseShiftDialog();
        }
    }

    [RelayCommand]
    private void ToggleDiscountDialog()
    {
        IsDiscountDialogOpen = !IsDiscountDialogOpen;
    }

    [RelayCommand]
    private async Task ApplyDiscountAsync()
    {
        if (!IsDiscountDialogOpen || SelectedCart is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            if (string.IsNullOrWhiteSpace(DiscountValueInput))
            {
                throw new InvalidOperationException("Введите сумму скидки");
            }

            var cart = CloneCart(SelectedCart);
            var discountValue = ParseMoney(DiscountValueInput);

            var subtotalRaw = cart.Items.Sum(x => x.Price * x.Quantity);
            var discount = DiscountType == "percent"
                ? Round2((subtotalRaw * discountValue) / 100m)
                : discountValue;

            cart.Discount = Math.Max(0, discount);
            RecalculateCart(cart);

            await SaveActiveCartAsync(cart, isDirty: true);
            IsDiscountDialogOpen = false;
            DiscountValueInput = string.Empty;

            _ = TrySyncNowAsync(forcePullFromServer: false, silent: true);
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
        if (SelectedCart is null || CurrentUser is null)
        {
            return;
        }

        await ExecuteBusyAsync(async () =>
        {
            var checkoutRequest = new CheckoutRequest
            {
                PaymentMethod = PaymentMethod,
                PaymentStatus = IsDebtPayment ? "debt" : "paid",
                Cash = ParseMoney(CashInput),
                CustomerName = string.IsNullOrWhiteSpace(CustomerName) ? null : CustomerName.Trim(),
                CustomerPhone = string.IsNullOrWhiteSpace(CustomerPhone) ? null : CustomerPhone.Trim(),
                Notes = string.IsNullOrWhiteSpace(OrderNote) ? null : OrderNote.Trim(),
            };

            if (checkoutRequest.PaymentStatus == "debt" && string.IsNullOrWhiteSpace(checkoutRequest.CustomerPhone))
            {
                throw new InvalidOperationException("Для оплаты в долг укажите телефон клиента");
            }

            var cart = CloneCart(SelectedCart);
            if (cart.Items.Count == 0)
            {
                throw new InvalidOperationException("Корзина пустая");
            }

            var userId = CurrentUser.Id.ToString(CultureInfo.InvariantCulture);
            var record = await _localStore.GetCartRecordAsync(userId, cart.Id)
                         ?? new LocalCartRecord
                         {
                             ClientId = cart.Id,
                             UserId = userId,
                             Cart = cart,
                             State = LocalCartStates.Active,
                             IsDirty = true,
                         };

            var synced = false;

            if (!IsOfflineMode)
            {
                try
                {
                    var serverCartId = await PushCartSnapshotToServerAsync(record);
                    await _apiClient.CheckoutAsync(serverCartId, checkoutRequest);
                    synced = true;
                }
                catch (Exception ex) when (IsConnectivityException(ex))
                {
                    SetOfflineMode(true);
                }
            }

            if (synced)
            {
                await _localStore.DeleteCartPermanentlyAsync(userId, cart.Id);
                RemoveCartFromCollection(cart.Id);
                StatusMessage = "Продажа оформлена";
            }
            else
            {
                record.State = LocalCartStates.CheckoutPending;
                record.IsDirty = true;
                record.PendingCheckout = checkoutRequest;
                await _localStore.SaveCartRecordAsync(record);
                StatusMessage = "Продажа сохранена офлайн и будет отправлена при появлении сети";
            }

            await DecreaseLocalProductStockAsync(cart.Items);
            if (synced)
            {
                await TrySyncNowAsync(forcePullFromServer: true, silent: true);
                await EnsureActiveCartAsync();
            }
            else
            {
                await RemoveAndRecreateWorkingCartAsync(cart.Id);
                _ = TrySyncNowAsync(forcePullFromServer: true, silent: true);
            }

            IsCheckoutDialogOpen = false;
            CashInput = "0";
            CustomerName = string.Empty;
            CustomerPhone = string.Empty;
            OrderNote = string.Empty;
            PaymentMethod = "Наличными";
        });
    }

    [RelayCommand]
    private async Task RefreshAllAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            await LoadLocalShiftAsync();
            await LoadProductsFromLocalAsync();
            await LoadCartsFromLocalAsync(SelectedCart?.Id);
            _ = TrySyncNowAsync(forcePullFromServer: true, silent: true);
        });
    }

    private async void SyncTimerOnTick(object? sender, EventArgs e)
    {
        await TrySyncNowAsync(forcePullFromServer: false, silent: true);
    }

    private async Task BootstrapAsync()
    {
        await ExecuteBusyAsync(async () =>
        {
            await _localStore.InitializeAsync();
            await TryRestoreSessionAsync();
        }, clearMessages: false);
    }

    private async Task TryRestoreSessionAsync()
    {
        var cachedUser = await _localStore.LoadCachedUserAsync();
        if (cachedUser is not null)
        {
            CurrentUser = cachedUser;
        }

        await LoadLocalShiftAsync();
        await LoadProductsFromLocalAsync();

        if (cachedUser is not null)
        {
            await LoadCartsFromLocalAsync();
        }

        var token = SessionStore.LoadToken();
        if (string.IsNullOrWhiteSpace(token))
        {
            CurrentScreen = AppScreen.Login;
            return;
        }

        _accessToken = token;
        _apiClient.SetBearerToken(token);

        try
        {
            var me = await _apiClient.MeAsync();
            CurrentUser = me.User;
            await _localStore.SaveCachedUserAsync(me.User);
            SetOfflineMode(false);

            var gotShiftFromServer = await TryFetchAndCacheCurrentShiftAsync();
            await RouteByShiftStateAsync();
            _ = TrySyncNowAsync(forcePullFromServer: true, silent: true);

            StatusMessage = gotShiftFromServer
                ? "Сессия восстановлена"
                : "Сессия восстановлена. Оффлайн режим";
        }
        catch (ApiException ex) when (ex.StatusCode == 401)
        {
            await ClearSessionAsync();
        }
        catch (Exception ex) when (IsConnectivityException(ex))
        {
            SetOfflineMode(true);

            if (CurrentUser is null)
            {
                CurrentScreen = AppScreen.Login;
                ErrorMessage = "Нет сети. Для входа требуется интернет.";
                return;
            }

            await RouteByShiftStateAsync();
            StatusMessage = "Оффлайн режим активирован";
        }
    }

    private async Task RouteByShiftStateAsync()
    {
        if (CurrentUser is null)
        {
            CurrentScreen = AppScreen.Login;
            return;
        }

        await LoadLocalShiftAsync();

        if (CurrentShift is not null && CurrentShift.Status == "open")
        {
            CurrentScreen = AppScreen.Pos;
            await LoadProductsFromLocalAsync();
            await EnsureActiveCartAsync();
            return;
        }

        CurrentScreen = AppScreen.Shift;
    }

    private async Task LoadLocalShiftAsync()
    {
        CurrentShift = await _localStore.LoadShiftAsync();
    }

    private async Task<bool> TryFetchAndCacheCurrentShiftAsync()
    {
        try
        {
            var shiftResponse = await _apiClient.GetCurrentShiftAsync();
            CurrentShift = shiftResponse.Shift;
            await _localStore.SaveShiftAsync(shiftResponse.Shift);
            SetOfflineMode(false);
            return true;
        }
        catch (Exception ex) when (IsConnectivityException(ex))
        {
            SetOfflineMode(true);
            return false;
        }
    }

    private async Task LoadProductsFromLocalAsync()
    {
        var search = string.IsNullOrWhiteSpace(BarcodeInput)
            ? SearchText
            : BarcodeInput;

        var products = await _localStore.LoadProductsAsync(search, activeOnly: true);

        Products.Clear();
        foreach (var product in products)
        {
            Products.Add(product);
        }

        OnPropertyChanged(nameof(ProductCount));
    }

    private async Task LoadCartsFromLocalAsync(int? preferredCartId = null)
    {
        if (CurrentUser is null)
        {
            Carts.Clear();
            CartItems.Clear();
            SelectedCart = null;
            OnPropertyChanged(nameof(CartCount));
            return;
        }

        var records = await _localStore.GetActiveCartRecordsAsync(CurrentUser.Id.ToString(CultureInfo.InvariantCulture));
        var carts = records.Select(x => CloneCart(x.Cart)).OrderByDescending(x => x.Id).ToList();

        Carts.Clear();
        foreach (var cart in carts)
        {
            Carts.Add(cart);
        }

        OnPropertyChanged(nameof(CartCount));

        if (carts.Count == 0)
        {
            SelectedCart = null;
            CartItems.Clear();
            return;
        }

        var targetId = preferredCartId ?? SelectedCart?.Id;
        SelectedCart = carts.FirstOrDefault(x => x.Id == targetId) ?? carts.First();
    }

    private async Task EnsureActiveCartAsync()
    {
        if (CurrentUser is null)
        {
            return;
        }

        await LoadCartsFromLocalAsync(SelectedCart?.Id);
        if (SelectedCart is not null)
        {
            return;
        }

        await CreateAndSelectNewLocalCartAsync();
    }

    private async Task CreateAndSelectNewLocalCartAsync()
    {
        if (CurrentUser is null)
        {
            throw new InvalidOperationException("Требуется авторизация");
        }

        var userId = CurrentUser.Id.ToString(CultureInfo.InvariantCulture);
        var record = await _localStore.CreateLocalCartAsync(userId);
        var cart = CloneCart(record.Cart);

        Carts.Insert(0, cart);
        OnPropertyChanged(nameof(CartCount));
        SelectedCart = cart;
    }

    private async Task AddItemToCurrentCartLocalAsync(AddCartItemRequest request)
    {
        await EnsureActiveCartAsync();

        if (SelectedCart is null)
        {
            throw new InvalidOperationException("Корзина не найдена");
        }

        var product = await FindProductForAddAsync(request);
        if (product is null)
        {
            throw new InvalidOperationException("Товар не найден в локальном кэше. Обновите каталог онлайн.");
        }

        var quantity = request.Quantity <= 0 ? 1 : request.Quantity;
        var cart = CloneCart(SelectedCart);

        var item = cart.Items.FirstOrDefault(x => x.ProductId == product.Id);
        if (item is null)
        {
            var nextItemId = cart.Items.Count == 0
                ? 1
                : cart.Items.Max(x => x.Id) + 1;

            item = new CartItemDto
            {
                Id = nextItemId,
                ProductId = product.Id,
                ProductName = product.Name,
                ProductSku = product.Sku,
                Price = product.SellingPrice,
                Quantity = quantity,
                Discount = 0,
                LineSubtotal = Round2(product.SellingPrice * quantity),
            };

            cart.Items.Add(item);
        }
        else
        {
            item.Quantity += quantity;
            item.LineSubtotal = Round2(item.Price * item.Quantity);
        }

        RecalculateCart(cart);
        await SaveActiveCartAsync(cart, isDirty: true);
    }

    private async Task<ProductDto?> FindProductForAddAsync(AddCartItemRequest request)
    {
        if (request.ProductId.HasValue)
        {
            var existing = Products.FirstOrDefault(x => x.Id == request.ProductId.Value);
            if (existing is not null)
            {
                return existing;
            }

            var all = await _localStore.LoadProductsAsync(activeOnly: true);
            return all.FirstOrDefault(x => x.Id == request.ProductId.Value);
        }

        if (!string.IsNullOrWhiteSpace(request.Sku))
        {
            var existing = Products.FirstOrDefault(x => string.Equals(x.Sku, request.Sku, StringComparison.OrdinalIgnoreCase));
            if (existing is not null)
            {
                return existing;
            }

            var all = await _localStore.LoadProductsAsync(activeOnly: true);
            return all.FirstOrDefault(x => string.Equals(x.Sku, request.Sku, StringComparison.OrdinalIgnoreCase));
        }

        return null;
    }

    private async Task SaveActiveCartAsync(CartDto cart, bool isDirty)
    {
        if (CurrentUser is null)
        {
            return;
        }

        var userId = CurrentUser.Id.ToString(CultureInfo.InvariantCulture);
        var existing = await _localStore.GetCartRecordAsync(userId, cart.Id);

        var record = existing ?? new LocalCartRecord
        {
            ClientId = cart.Id,
            UserId = userId,
            ServerId = null,
            State = LocalCartStates.Active,
        };

        record.Cart = CloneCart(cart);
        record.State = LocalCartStates.Active;
        record.IsDirty = isDirty;
        record.PendingCheckout = null;

        await _localStore.SaveCartRecordAsync(record);

        UpsertCartInCollection(record.Cart);
    }

    private void UpsertCartInCollection(CartDto cart)
    {
        var clone = CloneCart(cart);
        var existing = Carts.FirstOrDefault(x => x.Id == clone.Id);
        if (existing is null)
        {
            Carts.Insert(0, clone);
            OnPropertyChanged(nameof(CartCount));
            SelectedCart = clone;
            return;
        }

        var index = Carts.IndexOf(existing);
        Carts[index] = clone;

        if (SelectedCart?.Id == clone.Id)
        {
            SelectedCart = clone;
        }
    }

    private void RemoveCartFromCollection(int cartId)
    {
        var existing = Carts.FirstOrDefault(x => x.Id == cartId);
        if (existing is null)
        {
            return;
        }

        Carts.Remove(existing);
        OnPropertyChanged(nameof(CartCount));

        if (SelectedCart?.Id == cartId)
        {
            SelectedCart = Carts.FirstOrDefault();
        }
    }

    private async Task RemoveAndRecreateWorkingCartAsync(int currentCartId)
    {
        RemoveCartFromCollection(currentCartId);

        if (CurrentUser is null)
        {
            return;
        }

        var active = await _localStore.GetActiveCartRecordsAsync(CurrentUser.Id.ToString(CultureInfo.InvariantCulture));
        if (active.Count > 0)
        {
            await LoadCartsFromLocalAsync();
            if (SelectedCart is null)
            {
                SelectedCart = Carts.FirstOrDefault();
            }

            return;
        }

        await CreateAndSelectNewLocalCartAsync();
    }

    private async Task DecreaseLocalProductStockAsync(IReadOnlyCollection<CartItemDto> items)
    {
        if (items.Count == 0)
        {
            return;
        }

        var products = await _localStore.LoadProductsAsync();
        if (products.Count == 0)
        {
            return;
        }

        var changed = false;

        foreach (var item in items)
        {
            var product = products.FirstOrDefault(x => x.Id == item.ProductId);
            if (product is null)
            {
                continue;
            }

            var currentQty = ParseDecimal(product.QuantityRaw);
            var nextQty = currentQty - item.Quantity;
            product.QuantityRaw = nextQty.ToString(CultureInfo.InvariantCulture);
            changed = true;
        }

        if (!changed)
        {
            return;
        }

        await _localStore.SaveProductsAsync(products);
        await LoadProductsFromLocalAsync();
    }

    private async Task TrySyncNowAsync(bool forcePullFromServer, bool silent)
    {
        if (CurrentUser is null || string.IsNullOrWhiteSpace(_accessToken))
        {
            return;
        }

        if (!await _syncGate.WaitAsync(0))
        {
            return;
        }

        try
        {
            var userId = CurrentUser.Id.ToString(CultureInfo.InvariantCulture);

            var me = await _apiClient.MeAsync();
            CurrentUser = me.User;
            await _localStore.SaveCachedUserAsync(me.User);

            SetOfflineMode(false);

            var pendingShiftOpen = await _localStore.GetPendingShiftOpenAsync();
            if (pendingShiftOpen.HasValue)
            {
                var openResponse = await _apiClient.OpenShiftAsync(pendingShiftOpen.Value);
                CurrentShift = openResponse.Shift;
                await _localStore.SaveShiftAsync(openResponse.Shift);
                await _localStore.ClearPendingShiftOpenAsync();
            }

            await SyncCartsAsync(userId);

            var pendingShiftClose = await _localStore.GetPendingShiftCloseAsync();
            if (pendingShiftClose.HasValue)
            {
                var closeResponse = await _apiClient.CloseShiftAsync(pendingShiftClose.Value);
                CurrentShift = closeResponse.Shift;
                await _localStore.SaveShiftAsync(closeResponse.Shift);
                await _localStore.ClearPendingShiftCloseAsync();
                await _localStore.ClearPendingShiftOpenAsync();
                await _localStore.ClearUserCartsAsync(userId);
            }

            var shiftResponse = await _apiClient.GetCurrentShiftAsync();
            CurrentShift = shiftResponse.Shift;
            await _localStore.SaveShiftAsync(shiftResponse.Shift);

            if (forcePullFromServer || ProductCount == 0 || !_didFullActiveProductsSync)
            {
                var allActiveProducts = await LoadAllActiveProductsFromServerAsync();
                await _localStore.SaveProductsAsync(allActiveProducts);
                _didFullActiveProductsSync = true;
            }

            var cartsResponse = await _apiClient.GetCartsAsync();
            await _localStore.MergeServerCartsAsync(userId, cartsResponse.Items);

            var activeLocalCarts = await _localStore.GetActiveCartRecordsAsync(userId);
            if (CurrentShift is not null && CurrentShift.Status == "open" && activeLocalCarts.Count == 0)
            {
                var created = await _apiClient.CreateCartAsync();
                await _localStore.MergeServerCartsAsync(userId, [created.Cart]);
            }

            await LoadProductsFromLocalAsync();
            await LoadCartsFromLocalAsync(SelectedCart?.Id);

            if (!silent)
            {
                StatusMessage = "Синхронизация завершена";
            }
        }
        catch (ApiException ex)
        {
            if (ex.StatusCode == 401)
            {
                await ClearSessionAsync();
                return;
            }

            if (!silent)
            {
                ErrorMessage = ex.Message;
            }
        }
        catch (Exception ex) when (IsConnectivityException(ex))
        {
            SetOfflineMode(true);

            if (!silent)
            {
                StatusMessage = "Оффлайн режим. Данные будут синхронизированы автоматически";
            }
        }
        catch (Exception ex)
        {
            if (!silent)
            {
                ErrorMessage = "Ошибка синхронизации: " + ex.Message;
            }
        }
        finally
        {
            _syncGate.Release();
        }
    }

    private async Task<List<ProductDto>> LoadAllActiveProductsFromServerAsync()
    {
        const int perPage = 200;
        const int maxPages = 200;
        var page = 1;
        var lastPage = 1;
        var all = new List<ProductDto>();

        do
        {
            var response = await _apiClient.GetProductsAsync(
                search: null,
                status: "active",
                perPage: perPage,
                page: page);

            if (response.Items.Count > 0)
            {
                all.AddRange(response.Items);
            }

            var metaLastPage = response.Meta?.LastPage ?? 1;
            lastPage = Math.Max(1, metaLastPage);
            page++;
        } while (page <= lastPage && page <= maxPages);

        return all
            .GroupBy(x => x.Id)
            .Select(g => g.First())
            .OrderByDescending(x => x.Id)
            .ToList();
    }

    private async Task SyncCartsAsync(string userId)
    {
        var records = await _localStore.GetCartRecordsAsync(userId);
        foreach (var record in records.OrderBy(x => x.UpdatedAtUtc))
        {
            if (record.State == LocalCartStates.Deleted)
            {
                if (record.ServerId.HasValue)
                {
                    try
                    {
                        await _apiClient.DeleteCartAsync(record.ServerId.Value);
                    }
                    catch (ApiException ex) when (ex.StatusCode == 404)
                    {
                        // Already deleted on server.
                    }
                }

                await _localStore.DeleteCartPermanentlyAsync(userId, record.ClientId);
                continue;
            }

            if (record.State == LocalCartStates.CheckoutPending)
            {
                var serverCartId = record.ServerId;

                if (record.IsDirty || !serverCartId.HasValue)
                {
                    serverCartId = await PushCartSnapshotToServerAsync(record);
                }

                if (record.PendingCheckout is null)
                {
                    record.State = LocalCartStates.Active;
                    record.IsDirty = false;
                    await _localStore.SaveCartRecordAsync(record);
                    continue;
                }

                await _apiClient.CheckoutAsync(serverCartId!.Value, record.PendingCheckout);
                await _localStore.DeleteCartPermanentlyAsync(userId, record.ClientId);
                continue;
            }

            if (record.State == LocalCartStates.Active && record.IsDirty)
            {
                await PushCartSnapshotToServerAsync(record);
                record.State = LocalCartStates.Active;
                record.IsDirty = false;
                record.PendingCheckout = null;
                await _localStore.SaveCartRecordAsync(record);
            }
        }
    }

    private async Task<int> PushCartSnapshotToServerAsync(LocalCartRecord record)
    {
        if (record.ServerId.HasValue)
        {
            try
            {
                await _apiClient.DeleteCartAsync(record.ServerId.Value);
            }
            catch (ApiException ex) when (ex.StatusCode == 404)
            {
                // Missing on server is acceptable.
            }
        }

        var createResponse = await _apiClient.CreateCartAsync();
        var serverCartId = createResponse.Cart.Id;

        foreach (var item in record.Cart.Items)
        {
            if (item.Quantity <= 0)
            {
                continue;
            }

            var addResponse = await _apiClient.AddItemToCartAsync(serverCartId, new AddCartItemRequest
            {
                ProductId = item.ProductId,
                Sku = item.ProductSku,
                Quantity = item.Quantity,
            });

            if (item.Discount > 0)
            {
                var serverItem = addResponse.Cart.Items.FirstOrDefault(x => x.ProductId == item.ProductId);
                if (serverItem is null)
                {
                    var latestCart = await _apiClient.GetCartAsync(serverCartId);
                    serverItem = latestCart.Cart.Items.FirstOrDefault(x => x.ProductId == item.ProductId);
                }

                if (serverItem is not null)
                {
                    await _apiClient.UpdateCartItemAsync(serverCartId, serverItem.Id, new UpdateCartItemRequest
                    {
                        Discount = item.Discount,
                    });
                }
            }
        }

        if (record.Cart.Discount > 0)
        {
            await _apiClient.ApplyDiscountAsync(serverCartId, new ApplyDiscountRequest
            {
                Type = "fixed",
                Value = record.Cart.Discount,
            });
        }

        record.ServerId = serverCartId;
        return serverCartId;
    }

    private ShiftDto BuildLocalOpenShift(decimal initialCash)
    {
        return new ShiftDto
        {
            Id = CurrentShift?.Id ?? 0,
            Status = "open",
            InitialCash = initialCash,
            StartTime = DateTime.UtcNow.ToString("O"),
            UserId = CurrentUser?.Id.ToString(CultureInfo.InvariantCulture),
        };
    }

    private ShiftDto BuildLocalCloseShift(decimal finalCash)
    {
        return new ShiftDto
        {
            Id = CurrentShift?.Id ?? 0,
            Status = "closed",
            InitialCash = CurrentShift?.InitialCash ?? 0,
            FinalCash = finalCash,
            StartTime = CurrentShift?.StartTime,
            EndTime = DateTime.UtcNow.ToString("O"),
            UserId = CurrentUser?.Id.ToString(CultureInfo.InvariantCulture),
            SubTotal = CurrentShift?.SubTotal,
            Total = CurrentShift?.Total,
            Discounts = CurrentShift?.Discounts,
            Debts = CurrentShift?.Debts,
            Expence = CurrentShift?.Expence,
        };
    }

    private void EnsureOnlineActionAllowed()
    {
        if (CurrentUser is null || string.IsNullOrWhiteSpace(_accessToken))
        {
            throw new InvalidOperationException("Требуется авторизация");
        }

        if (IsOfflineMode)
        {
            throw new InvalidOperationException("Это действие доступно только онлайн");
        }
    }

    private void EnsureOpenShift()
    {
        if (CurrentShift is null || !string.Equals(CurrentShift.Status, "open", StringComparison.OrdinalIgnoreCase))
        {
            throw new InvalidOperationException("Смена должна быть открыта");
        }
    }

    private void ResetAddExpenseForm()
    {
        ExpenseTotalInput = string.Empty;
        ExpenseDescriptionInput = string.Empty;
    }

    private void ResetPayDebtForm()
    {
        DebtPhoneInput = string.Empty;
        DebtTotalInput = string.Empty;
        DebtCustomerNameInput = string.Empty;
        DebtCustomerStatus = string.Empty;
    }

    private void ResetAddProductForm()
    {
        NewProductSkuInput = string.Empty;
        NewProductQuantityInput = "1";
        NewProductNameInput = string.Empty;
        NewProductPriceInput = string.Empty;
        NewProductStatus = "active";
    }

    private void RecalculateCart(CartDto cart)
    {
        foreach (var item in cart.Items)
        {
            item.LineSubtotal = Round2(item.Price * item.Quantity);
        }

        var subtotalRaw = cart.Items.Sum(x => x.Price * x.Quantity);
        var itemDiscount = cart.Items.Sum(x => x.Discount);
        var totalDiscount = Math.Max(0, cart.Discount + itemDiscount);

        cart.Subtotal = Round2(subtotalRaw);
        cart.Total = Round2(Math.Max(0, subtotalRaw - totalDiscount));
    }

    private async Task ExecuteBusyAsync(Func<Task> action, bool clearMessages = true)
    {
        if (IsBusy)
        {
            return;
        }

        try
        {
            IsBusy = true;

            if (clearMessages)
            {
                ErrorMessage = string.Empty;
                StatusMessage = string.Empty;
            }

            await action();
        }
        catch (ApiException ex)
        {
            if (ex.StatusCode == 401)
            {
                await ClearSessionAsync();
                return;
            }

            ErrorMessage = ex.Message;
        }
        catch (Exception ex) when (IsConnectivityException(ex))
        {
            SetOfflineMode(true);
            ErrorMessage = "Соединение потеряно. Работа продолжается в офлайн режиме.";
        }
        catch (Exception ex)
        {
            ErrorMessage = ex.Message;
        }
        finally
        {
            IsBusy = false;
        }
    }

    private void SetOfflineMode(bool isOffline)
    {
        IsOfflineMode = isOffline;
    }

    private async Task ClearSessionAsync()
    {
        SessionStore.Clear();

        _accessToken = null;
        _apiClient.ClearBearerToken();
        _didFullActiveProductsSync = false;

        CurrentUser = null;
        CurrentShift = null;
        CurrentScreen = AppScreen.Login;
        IsOfflineMode = false;

        Carts.Clear();
        Products.Clear();
        CartItems.Clear();
        SelectedCart = null;

        OnPropertyChanged(nameof(CartCount));
        OnPropertyChanged(nameof(ProductCount));

        await _localStore.ClearAllAsync();
    }

    private static bool IsConnectivityException(Exception ex)
    {
        return ex is HttpRequestException || ex is TaskCanceledException;
    }

    private static CartDto CloneCart(CartDto source)
    {
        return new CartDto
        {
            Id = source.Id,
            UserId = source.UserId,
            Discount = source.Discount,
            Subtotal = source.Subtotal,
            Total = source.Total,
            Items = source.Items.Select(CloneCartItem).ToList(),
        };
    }

    private static CartItemDto CloneCartItem(CartItemDto source)
    {
        return new CartItemDto
        {
            Id = source.Id,
            ProductId = source.ProductId,
            ProductName = source.ProductName,
            ProductSku = source.ProductSku,
            Price = source.Price,
            Quantity = source.Quantity,
            Discount = source.Discount,
            LineSubtotal = source.LineSubtotal,
        };
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

    private static int ParsePositiveInt(string? raw, string fieldLabel)
    {
        if (string.IsNullOrWhiteSpace(raw))
        {
            throw new InvalidOperationException($"Введите {fieldLabel}");
        }

        var normalized = raw.Trim().Replace(" ", string.Empty);
        if (!int.TryParse(normalized, NumberStyles.Integer, CultureInfo.InvariantCulture, out var value)
            && !int.TryParse(normalized, NumberStyles.Integer, CultureInfo.CurrentCulture, out value))
        {
            throw new InvalidOperationException($"Неверный формат поля '{fieldLabel}': {raw}");
        }

        if (value <= 0)
        {
            throw new InvalidOperationException($"Поле '{fieldLabel}' должно быть больше 0");
        }

        return value;
    }

    private static decimal ParseDecimal(string? raw)
    {
        if (string.IsNullOrWhiteSpace(raw))
        {
            return 0;
        }

        var normalized = raw.Trim().Replace(',', '.');
        return decimal.TryParse(normalized, NumberStyles.Any, CultureInfo.InvariantCulture, out var value)
            ? value
            : 0;
    }

    private static decimal Round2(decimal value)
    {
        return Math.Round(value, 2, MidpointRounding.AwayFromZero);
    }
}
