using Avalonia.Controls;
using Avalonia.Input;
using Avalonia.Threading;
using PosDesktop.ViewModels;

namespace PosDesktop.Views;

public partial class MainWindow : Window
{
    public MainWindow()
    {
        InitializeComponent();
    }

    private async void BarcodeInputTextBox_OnKeyDown(object? sender, KeyEventArgs e)
    {
        if (e.Key != Key.Enter && e.Key != Key.Return)
        {
            return;
        }

        e.Handled = true;

        if (DataContext is not MainWindowViewModel vm)
        {
            return;
        }

        await vm.AddByBarcodeCommand.ExecuteAsync(null);

        // Keep scanner workflow fast: always return caret to barcode field.
        await Dispatcher.UIThread.InvokeAsync(() => BarcodeInputTextBox.Focus(), DispatcherPriority.Background);
    }
}
